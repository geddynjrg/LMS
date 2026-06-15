@props(['id', 'title', 'type' => 'info', 'icon' => null, 'size' => 'modal-lg', 'wireIgnore' => true])

@php
    $colorMap = [
        'reject'  => [
            'ring' => 'rgba(220, 53, 69, 0.2)',
            'light' => '#fdecea',
            'text' => 'text-danger',
            'icon' => $icon ?: 'fa-times-circle'
        ],
        'approve', 'info'    => [
            'ring' => 'rgba(23, 162, 184, 0.2)',
            'light' => '#e7f6f8',
            'text' => 'text-info',
            'icon' => $icon ?: 'fa-info-circle'
        ],
    ];
    $theme = $colorMap[$type] ?? $colorMap['info'];
@endphp

<div @if($wireIgnore) wire:ignore.self @endif
    id="{{ $id }}"
    tabindex="-1"
    role="dialog"
    x-data="{ isShaking: false }"
    @form-validation-failed.window="isShaking = true; setTimeout(() => isShaking = false, 600);"
    {{ $attributes->merge(['class' => 'modal fade custom-action-modal']) }}
>
    <div
        class="modal-dialog modal-dialog-centered {{ $size }}"
        :class="{ 'shake-modal': isShaking }"
        role="document"
    >

        <div class="modal-content modern-modal-content">
            <div class="modal-header border-0 pt-5 d-block text-center">
                <div class="icon-wrapper animate__animated animate__pulse animate__infinite" style="background: {{ $theme['light'] }}; box-shadow: 0 0 0 8px {{ $theme['ring'] }};">
                    <i class="fas {{ $theme['icon'] }} {{ $theme['text'] }} animate-icon"></i>
                </div>
                <h4 class="modal-title w-100 font-weight-bold mt-4 text-dark">{{ $title }}</h4>
            </div>

            <div class="modal-body px-5 py-2">
                <div class="modal-text-content">
                    {{ $slot }}
                </div>
            </div>

            <div class="modal-footer border-0 pb-5 pt-4 justify-content-center">
                {{ $footer ?? '' }}
                @if(!isset($footer))
                    <x-pill-button label="Cancel" variant="outline" color="dark" data-dismiss="modal" class="mx-1" />
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    /* 1. Modal Container & Animation */
    .modern-modal-content {
        height: auto !important;
        overflow: visible !important;
        display: flex;
        flex-direction: column;
        border-radius: 24px !important;
        border: none !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        background-color: #ffffff;
    }

    .modal.custom-action-modal.fade .modal-dialog {
        transform: scale(0.85);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        max-height: none !important;
        margin-top: 50px;
        margin-bottom: 50px;
    }

    .modal.custom-action-modal {
        overflow-y: auto !important;
    }

    .custom-action-modal .modal-body {
        overflow: visible !important; /* Let the content flow out */
        max-height: none !important;
        flex: none; /* Disable the flex-shrink behavior */
    }

    /* 2. Icon Styling & Animation */
    .icon-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        position: relative;
    }
    .icon-wrapper i { font-size: 2.2rem; }

    .animate-icon {
        display: inline-block;
        animation: iconPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }

    @keyframes iconPop {
        0% { transform: scale(0); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }

    /* Shake animation for validation errors */
    .shake-modal {
        animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
    }

    @keyframes shake {
        10%, 90% { transform: translate3d(-1px, 0, 0); }
        20%, 80% { transform: translate3d(2px, 0, 0); }
        30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
        40%, 60% { transform: translate3d(4px, 0, 0); }
    }

    /* 3. Metrics: Original Applied Amount Card */
    .applied-amount-badge {
        background: linear-gradient(135deg, #77126a 0%, hsl(295, 75%, 37%) 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 18px;
        box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4);
        border: 1px solid rgba(255,255,255,0.1);
    }

    /* Icon Section with Scanning Effect */
    .gfs-icon-section {
        background: #f1f5f9;
        padding: 12px 20px;
        border-right: 2px solid #e2e8f0;
        color: #64748b;
        font-size: 1.4rem;
        position: relative;
    }

    .scanner-line {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 2px;
        background: rgba(59, 130, 246, 0.5);
        animation: scan 2s linear infinite;
    }

    /* Code Text Section */
    .gfs-container {
        display: flex;
        align-items: center;
        background: #fdfdfe; /* Slightly brighter */
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        height: 50px; /* Locked height for alignment */
        transition: border-color 0.3s ease;
    }

    .gfs-icon-section {
        background: #f1f5f9;
        padding: 0 15px;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        border-right: 1px solid #e2e8f0;
        color: #475569;
        font-size: 1.1rem;
        position: relative;
    }

    /* Reduced size and tighter spacing */
    .gfs-code-section {
        flex: 1;
        padding: 0 12px;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 1.1rem; /* Reduced from 1.25rem */
        letter-spacing: 0.5px; /* Reduced from 2px - much more readable */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
    }

    .gfs-prefix {
        color: #94a3b8;
        font-weight: 500;
    }

    .gfs-dynamic-code {
        color: #2563eb;
        font-weight: 700;
        padding-left: 2px;
    }

    .gfs-status-badge {
        background: #f0fdf4;
        color: #166534;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 4px 10px;
        border-radius: 6px;
        margin-right: 10px;
        border: 1px solid #dcfce7;
    }

    /* Scanner Animation */
    @keyframes scan {
        0% { transform: translateY(0); opacity: 0; }
        50% { opacity: 1; }
        100% { transform: translateY(48px); opacity: 0; }
    }
    .scanner-line {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 2px;
        background: #3b82f6;
        animation: scan 2s linear infinite;
    }

    /* 5. Finance Input (Large Currency Display) */
    .finance-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .currency-label {
        position: absolute;
        left: 20px;
        font-weight: 800;
        color: hsl(297, 73%, 46%);
        font-size: 1.2rem;
    }
    .finance-input {
        width: 100%;
        padding: 1.2rem 1.2rem 1.2rem 4.5rem !important;
        font-size: 2.2rem !important;
        font-weight: 800 !important;
        color: #1e293b;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        background: #f8fafc;
        transition: all 0.2s ease;
    }
    .finance-input:focus {
        border-color: #10b981;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        outline: none;
    }

    /* 6. Form Elements & Selects */
    .custom-action-modal .form-control:not(.finance-input) {
        border-radius: 12px;
        border: 2px solid #f1f5f9;
        padding: 12px 16px;
        background-color: #f8fafc;
        font-weight: 500;
        transition: all 0.2s;
    }
    .custom-action-modal .form-control:focus {
        border-color: #3b82f6;
        background-color: #fff;
        box-shadow: none;
    }

    .select-wrapper { position: relative; }
    .custom-select-premium {
        appearance: none;
        -webkit-appearance: none;
        padding-right: 40px !important;
    }
    .select-arrow {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #94a3b8;
    }

    /* 7. Utility & Overrides */
    .modal-xl { max-width: 1000px; }
    .modal-body { overflow: visible !important; }
    .modal-text-content { color: #334155; }
    .border-right { border-right: 2px solid #f1f5f9 !important; }

    /* Add this for the smaller confirmation modals */
    .modal-md { max-width: 450px; }

    /* Center the text if it's a simple confirmation */
    .modal-text-content.text-center {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .border-left-primary {
        border-left: 4px solid #007bff !important;
    }

    .tracking-wider {
        letter-spacing: 0.12em;
    }

    #modal-next-step .list-group-item {
        border-color: #f1f5f9;
        font-size: 1.05rem; /* Larger base font for list items */
    }

    #modal-next-step .modal-content {
        border-top: 5px solid #28a745; /* Success accent top border */
    }
</style>
