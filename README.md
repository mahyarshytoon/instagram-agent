# Instagram Agent 🤖

> یه ربات هوشمند Laravel که با AI به کامنت‌های اینستاگرام جواب می‌ده.
> An AI-powered Laravel bot that replies to Instagram comments.

---

## 🇮🇷 فارسی

### چیه؟
یه ربات Laravel که به کامنت‌های اینستاگرام با AI جواب می‌ده.
مغز ربات: Laravel AI SDK + RAG + Function Calling.

### ✨ ویژگی‌ها
- 🤖 جواب‌دهی خودکار با AI (OpenRouter + deepseek-v4-flash)
- 🔍 RAG با Function Calling (GetProductInfo + SearchProducts)
- 🛡️ SpamDetector فارسی (فحش‌ها و لینک‌های ناخواسته)
- ⏱️ Rate Limiter (کنترل تعداد کامنت)
- 📊 پنل ادمین (Dashboard, Comments, Conversations, Settings, Meta Logs)
- 🎭 Demo Page (شبیه اینستاگرام) + FakeMetaServer (تست بدون Meta واقعی)
- 🧪 ۳۶ تست PHPUnit
- 📦 معماری MetaAdapter (Fake ↔ Real)

### 🛠️ تکنولوژی
- Laravel 13.33
- PHP 8.4.12
- MySQL + Redis
- OpenRouter (deepseek/deepseek-v4-flash-0731)
- Laravel AI SDK

### 🚀 نصب
```bash
git clone https://github.com/USERNAME/instagram-agent.git
cd instagram-agent
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve


🇬🇧 English
What is it?

A Laravel bot that replies to Instagram comments using AI.
Core: Laravel AI SDK + RAG + Function Calling.
✨ Features

    🤖 AI auto-reply (OpenRouter + deepseek-v4-flash)

    🔍 RAG with Function Calling (GetProductInfo + SearchProducts)

    🛡️ Persian SpamDetector

    ⏱️ Rate Limiter

    📊 Admin Panel (Dashboard, Comments, Conversations, Settings, Meta Logs)

    🎭 Demo Page + FakeMetaServer (test without real Meta)

    🧪 36 PHPUnit tests

    📦 MetaAdapter Pattern (Fake ↔ Real)

🛠️ Tech Stack

    Laravel 13.33

    PHP 8.4.12

    MySQL + Redis

    OpenRouter (deepseek/deepseek-v4-flash-0731)

    Laravel AI SDK

🚀 Installation