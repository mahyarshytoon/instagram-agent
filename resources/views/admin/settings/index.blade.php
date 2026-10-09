
{{-- resources/views/admin/settings/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'تنظیمات')
@section('page-title', '⚙️ تنظیمات')

@section('content')

{{-- ═══════ پیام موفقیت / خطا ═══════ --}}
@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border-r-4 border-green-500 rounded-lg">
        <p class="text-green-700 font-medium">{{ session('success') }}</p>
    </div>
@endif

@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border-r-4 border-red-500 rounded-lg">
        <p class="text-red-700 font-medium">{{ session('error') }}</p>
    </div>
@endif

{{-- ═══════ تب‌ها ═══════ --}}
<div class="bg-white rounded-xl shadow-sm mb-6 overflow-hidden">
    <div class="flex border-b border-gray-200 overflow-x-auto">
        @foreach($groups as $groupKey => $groupData)
            @php
                $isActive = $activeTab === $groupKey;
                $colorMap = [
                    'red'    => 'border-red-500 text-red-600',
                    'amber'  => 'border-amber-500 text-amber-600',
                    'purple' => 'border-purple-500 text-purple-600',
                    'indigo' => 'border-indigo-500 text-indigo-600',
                ];
                $activeClass = $colorMap[$groupData['color'] ?? 'indigo'] ?? 'border-indigo-500 text-indigo-600';
            @endphp
            <a href="{{ route('admin.settings.index', ['tab' => $groupKey]) }}"
               class="px-6 py-4 font-bold text-sm whitespace-nowrap border-b-2 transition
                   {{ $isActive
                        ? $activeClass . ' bg-gray-50'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                {{ $groupData['label'] }}
            </a>
        @endforeach
    </div>
</div>

{{-- ═══════ محتوای تب فعال ═══════ --}}
@php
    $group = $groups[$activeTab] ?? null;
@endphp

@if($group)
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <input type="hidden" name="_group" value="{{ $activeTab }}">

        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            {{-- توضیح گروه --}}
            @if(!empty($group['description']))
                <p class="text-sm text-gray-500 mb-6 pb-4 border-b border-gray-100">
                    {{ $group['description'] }}
                </p>
            @endif

            {{-- فیلدها --}}
            <div class="space-y-6">
                @foreach($group['fields'] ?? [] as $key => $field)
                    @php
                        $inputName = str_replace('.', '_', $key);
                        $value = old($inputName, $values[$key] ?? null);
                        $type = $field['type'] ?? 'string';
                    @endphp

                    <div>
                        <label for="{{ $inputName }}" class="block text-sm font-bold text-gray-700 mb-2">
                            {{ $field['label'] }}
                        </label>

                        {{-- ─── نوع: array → textarea (هر خط یه آیتم) ─── --}}
                        @if($type === 'array')
                            <textarea
                                id="{{ $inputName }}"
                                name="{{ $inputName }}"
                                rows="8"
                                dir="ltr"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="هر خط یک آیتم...">{{ is_array($value) ? implode("\n", $value) : $value }}</textarea>

                        {{-- ─── نوع: int / float → number ─── --}}
                        @elseif($type === 'int' || $type === 'float')
                            <input
                                type="number"
                                id="{{ $inputName }}"
                                name="{{ $inputName }}"
                                value="{{ $value }}"
                                @if($type === 'float') step="0.1" @endif
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">

                        {{-- ─── نوع: bool → checkbox ─── --}}
                        @elseif($type === 'bool')
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    id="{{ $inputName }}"
                                    name="{{ $inputName }}"
                                    value="1"
                                    {{ $value ? 'checked' : '' }}
                                    class="w-5 h-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                <span class="text-sm text-gray-600">فعال</span>
                            </label>

                        {{-- ─── نوع: select → dropdown ─── --}}
                        @elseif($type === 'select')
                            <select
                                id="{{ $inputName }}"
                                name="{{ $inputName }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                @foreach($field['options'] ?? [] as $optValue => $optLabel)
                                    <option value="{{ $optValue }}" {{ $value == $optValue ? 'selected' : '' }}>
                                        {{ $optLabel }}
                                    </option>
                                @endforeach
                            </select>

                        {{-- ─── نوع: secret → password ─── --}}
                        @elseif($type === 'secret')
                            <input
                                type="password"
                                id="{{ $inputName }}"
                                name="{{ $inputName }}"
                                value="{{ $value }}"
                                dir="ltr"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="اگه خالی بذاری، مقدار قبلی حفظ می‌شه">

                        {{-- ─── نوع: text → textarea ─── --}}
                        @elseif($type === 'text')
                            <textarea
                                id="{{ $inputName }}"
                                name="{{ $inputName }}"
                                rows="5"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">{{ $value }}</textarea>

                        {{-- ─── نوع: string (پیش‌فرض) → text ─── --}}
                        @else
                            <input
                                type="text"
                                id="{{ $inputName }}"
                                name="{{ $inputName }}"
                                value="{{ $value }}"
                                dir="ltr"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        @endif

                        {{-- راهنما --}}
                        @if(!empty($field['help']))
                            <p class="mt-2 text-xs text-gray-500">{{ $field['help'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- دکمه‌ی ذخیره --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.settings.index', ['tab' => $activeTab]) }}"
               class="px-6 py-3 bg-gray-200 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-300 transition">
                بازنشانی
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-l from-pink-500 to-purple-600 text-white text-sm font-bold rounded-lg hover:shadow-lg transition">
                💾 ذخیره تغییرات
            </button>
        </div>

    </form>
@endif

@endsection