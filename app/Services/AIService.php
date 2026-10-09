<?php
// app/Services/AIService.php

namespace App\Services;

use App\Ai\Agents\ProductSalesAgent;
use App\Models\AdminSetting;
use App\Models\Comment;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class AIService
{
    /**
     * تولید جواب AI برای یه کامنت
     *
     * @return array{
     *     success: bool,
     *     text: string,
     *     model: string,
     *     input_tokens: int,
     *     output_tokens: int,
     *     total_tokens: int,
     *     error: ?string
     * }
     */
    public function generateReply(
        Comment $comment,
        ?Product $product = null,
        array $history = []
    ): array {
        // ═══ ۱. مدل و پارامترها رو از AdminSetting (fallback به config) ═══
        $model       = (string) AdminSetting::get('ai.model',       config('agent.ai.model', 'deepseek/deepseek-v4-flash-0731'));
        $temperature = (float)  AdminSetting::get('ai.temperature', config('agent.ai.temperature', 0.7));
        $maxTokens   = (int)    AdminSetting::get('ai.max_tokens',  config('agent.ai.max_tokens', 1000));
        $customPrompt = (string) AdminSetting::get('ai.system_prompt', '');

        try {
			
			Log::info('AIService: using model', ['model' => $model, 'tokens_setting' => $maxTokens]);
            // ═══ ۲. محصولات پست ═══
            $products = [];
            if ($comment->post) {
                $products = $comment->post->products()
                    ->where('is_active', true)
                    ->get()
                    ->all();
            }

            // اگه محصول مستقیم داده شده و تو لیست نیست، اضافه کن
            if ($product && ! in_array($product->id, array_column($products, 'id'))) {
                $products[] = $product;
            }

            // ═══ ۳. Agent بساز ═══
            $agent = new ProductSalesAgent(
                products: $products,
                model: $model,
                temperature: $temperature,
                maxTokens: $maxTokens,
                customPrompt: $customPrompt,
            );

            // ═══ ۴. تاریخچه + پیام جدید ═══
            $userText = $comment->sanitized_text ?? $comment->text;

            $messages = [];
            foreach ($history as $msg) {
                if (! isset($msg['role'], $msg['content'])) {
                    continue;
                }
                // فقط role های user و assistant رو قبول کن
                if (! in_array($msg['role'], ['user', 'assistant'], true)) {
                    continue;
                }
                $messages[] = [
                    'role'    => $msg['role'],
                    'content' => (string) $msg['content'],
                ];
            }

            // اگه تاریخچه داریم، از withMessages استفاده کن
            if (! empty($messages)) {
                $response = $agent->withMessages($messages)->prompt($userText);
            } else {
                $response = $agent->prompt($userText);
            }

            // ═══ ۵. tokens واقعی ═══
            $inputTokens  = $response->usage->inputTokens  ?? 0;
            $outputTokens = $response->usage->outputTokens ?? 0;
			Log::info('AIService: result', [
					'input_tokens' => $inputTokens,
					'output_tokens' => $outputTokens,
					'model' => $response->meta->model ?? $model,
			]);
            return [
                'success'       => true,
                'text'          => $response->text,
                'model'         => $response->meta->model ?? $model,
                'input_tokens'  => (int) $inputTokens,
                'output_tokens' => (int) $outputTokens,
                'total_tokens'  => (int) ($inputTokens + $outputTokens),
                'error'         => null,
            ];
        } catch (\Throwable $e) {
            Log::error('AIService: AI call failed', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return [
                'success'       => false,
                'text'          => '',
                'model'         => $model,
                'input_tokens'  => 0,
                'output_tokens' => 0,
                'total_tokens'  => 0,
                'error'         => $e->getMessage(),
            ];
        }
    }
}