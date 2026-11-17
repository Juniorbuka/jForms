<!doctype html>
<html class="evo-ui-page {{ $themeClasses }}" lang="{{ $uiLocale }}" data-theme="{{ $theme }}" data-theme-mode="{{ $themeMode }}" style="background-color: var(--evo-ui-bg, {{ $themeBackground }})">
<head>
    <meta charset="utf-8">
    <meta name="color-scheme" content="{{ $themeMode === 'dark' ? 'dark light' : 'light dark' }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', __('jForms::module.title', [], $uiLocale))</title>
    @include('evo::partials.assets')
    <style>
        .evo-ui-jforms{max-width:1180px;margin:0 auto;padding:24px}
        .evo-ui-jforms__header{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:20px}
        .evo-ui-jforms__header h1{margin:0;font-size:22px}
        .evo-ui-jforms__header p{margin:5px 0 0;color:var(--evo-ui-muted);font-size:13px}
        .evo-ui-jforms__header-icon{width:42px;height:42px;flex:none;color:var(--evo-ui-primary)}
        .evo-ui-jforms__back{display:inline-flex;align-items:center;gap:6px;margin:0 0 16px;color:var(--evo-ui-primary);text-decoration:none}
        .evo-ui-jforms__back:hover{text-decoration:underline}
        .evo-ui-jforms__form-grid{display:grid;gap:12px}
        .evo-ui-jforms__form-link{display:grid;grid-template-columns:auto minmax(0,1fr) auto auto auto;align-items:center;gap:14px;padding:15px 17px;color:inherit;text-decoration:none;background:var(--evo-ui-surface);border:1px solid var(--evo-ui-border);border-radius:var(--evo-ui-radius)}
        .evo-ui-jforms__form-link:hover{border-color:var(--evo-ui-primary)}
        .evo-ui-jforms__form-info{display:grid;gap:3px;min-width:0}
        .evo-ui-jforms__form-info small,.evo-ui-jforms__form-date small{color:var(--evo-ui-muted);font-size:12px}
        .evo-ui-jforms__form-date{display:grid;gap:3px;font-size:12px;text-align:right}
        .evo-ui-jforms__response-list{display:grid;gap:12px}
        .evo-ui-jforms__response-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
        .evo-ui-jforms__date{display:inline-flex;align-items:center;gap:6px;color:var(--evo-ui-muted);font-size:12px}
        .evo-ui-jforms__data{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-bottom:14px}
        .evo-ui-jforms__field{display:grid;gap:4px;min-width:0;padding:11px 13px;background:var(--evo-ui-bg);border-radius:var(--evo-ui-radius)}
        .evo-ui-jforms__field-label{color:var(--evo-ui-muted);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
        .evo-ui-jforms__field-value{overflow-wrap:anywhere}
        .evo-ui-jforms__actions{display:flex;flex-wrap:wrap;align-items:center;gap:8px;padding-top:12px;border-top:1px solid var(--evo-ui-border)}
        .evo-ui-jforms__actions form{margin:0}
        .evo-ui-jforms__empty{padding:28px;text-align:center;color:var(--evo-ui-muted)}
        .evo-ui-jforms__detail{max-width:850px}
        .evo-ui-jforms__detail .evo-ui-jforms__data{grid-template-columns:1fr}
        .evo-ui-jforms__detail .evo-ui-jforms__field{grid-template-columns:minmax(130px,.35fr) 1fr;align-items:center}
        .evo-ui-jforms__notice{margin-bottom:14px;padding:12px 14px;border:1px solid var(--evo-ui-border);border-radius:var(--evo-ui-radius);background:var(--evo-ui-surface)}
        .evo-ui-jforms__filters{display:flex;align-items:end;flex-wrap:wrap;gap:10px;margin-bottom:14px;padding:15px;background:var(--evo-ui-surface);border:1px solid var(--evo-ui-border);border-radius:var(--evo-ui-radius)}
        .evo-ui-jforms__filters label{display:grid;gap:5px;color:var(--evo-ui-muted);font-size:12px;font-weight:600}
        .evo-ui-jforms__search{flex:1;min-width:220px}.evo-ui-jforms__search input{width:100%}
        .evo-ui-jforms__bulk{display:flex;align-items:center;gap:12px;margin:0 0 12px}.evo-ui-jforms__bulk>label,.evo-ui-jforms__select{display:inline-flex;align-items:center;gap:8px}
        .evo-ui-jforms__select input,.evo-ui-jforms__bulk input{accent-color:var(--evo-ui-primary)}
        .evo-ui-jforms__response-head .evo-ui-jforms__select{margin-right:auto}.evo-ui-jforms__pagination{margin-top:18px;overflow-x:auto}
        .evo-ui-jforms__response{padding:10px 14px!important}
        .evo-ui-jforms__response .evo-ui-jforms__response-head{margin-bottom:7px}
        .evo-ui-jforms__response-row{display:flex;align-items:center;gap:12px;min-width:0}
        .evo-ui-jforms__response .evo-ui-jforms__data{display:flex;flex:1;flex-wrap:wrap;align-items:baseline;gap:5px 18px;min-width:0;margin:0}
        .evo-ui-jforms__response .evo-ui-jforms__field{display:flex;align-items:baseline;gap:7px;min-width:0;padding:0;background:transparent}
        .evo-ui-jforms__response .evo-ui-jforms__field-label{flex:none;font-size:10px}
        .evo-ui-jforms__response .evo-ui-jforms__field-value{font-size:13px}
        .evo-ui-jforms__response .evo-ui-jforms__actions{flex:none;gap:6px;padding:0;border:0}
        .evo-ui-jforms__response .evo-ui-jforms__actions form{display:flex}
        .evo-ui-jforms__icon-btn{display:inline-flex;width:34px;height:34px;min-height:34px;align-items:center;justify-content:center;padding:0}
        .evo-ui-jforms__icon-btn svg{width:17px;height:17px}
        .evo-ui-jforms__date{white-space:nowrap}
        @media(max-width:680px){.evo-ui-jforms{padding:16px}.evo-ui-jforms__form-link{grid-template-columns:auto minmax(0,1fr) auto}.evo-ui-jforms__form-date{grid-column:2;text-align:left}.evo-ui-jforms__form-arrow{display:none}.evo-ui-jforms__detail .evo-ui-jforms__field{grid-template-columns:1fr}}
        @media(max-width:560px){.evo-ui-jforms__response-row{align-items:flex-start;flex-direction:column}.evo-ui-jforms__response .evo-ui-jforms__actions{align-self:flex-end}.evo-ui-jforms__date{font-size:11px}}
    </style>
</head>
<body class="evo-ui-page {{ $themeClasses }}" data-theme="{{ $theme }}" data-theme-mode="{{ $themeMode }}" style="background-color: var(--evo-ui-bg, {{ $themeBackground }})">
    <div class="evo-ui {{ $themeClasses }}" data-evo-ui-root data-theme="{{ $theme }}" data-theme-mode="{{ $themeMode }}">
        <main class="evo-ui-jforms">@yield('content')</main>
    </div>
</body>
</html>
