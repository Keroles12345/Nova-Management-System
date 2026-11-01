<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · NOVA</title>
    <style>
        :root{font-family:Tahoma,Arial,sans-serif;color:#20283d;background:#f5f7fb;font-synthesis:none;text-rendering:optimizeLegibility}
        *{box-sizing:border-box}body{margin:0}a{color:inherit;text-decoration:none}button,input,select,textarea{font:inherit}
        .shell{min-height:100vh;display:grid;grid-template-columns:250px minmax(0,1fr)}
        .sidebar{background:#171d31;color:#c0c6d8;padding:26px 18px;display:flex;flex-direction:column;gap:32px}
        .brand{display:flex;align-items:center;gap:12px;padding:0 10px;color:#fff;font-size:19px;font-weight:800;letter-spacing:.3px}
        .brand-mark{width:38px;height:38px;border-radius:13px;background:linear-gradient(145deg,#8c7bff,#5549d6);display:grid;place-items:center;color:#fff;font-size:20px;box-shadow:0 8px 20px #6555dc66}
        .brand small{display:block;color:#9199b2;font-size:10px;font-weight:500;letter-spacing:1.6px;margin-top:3px}
        .nav-label{font-size:10px;color:#8189a2;padding:0 12px 9px;letter-spacing:1.2px;font-weight:700}
        .nav-list{display:grid;gap:5px}.nav-link{display:flex;align-items:center;gap:12px;padding:12px;border-radius:10px;font-size:13px;transition:.18s}
        .nav-link:hover,.nav-link.active{background:#292f48;color:white}.nav-icon{width:22px;text-align:center;color:#a8a2ff;font-size:16px}
        .sidebar-bottom{margin-top:auto;border-top:1px solid #ffffff17;padding-top:18px}.role-chip{display:inline-block;background:#ffffff14;border-radius:999px;padding:6px 10px;color:#d4d7e5;font-size:10px;margin:0 10px 14px}
        .logout{width:100%;border:0;background:transparent;color:#bec4d5;text-align:left;padding:11px 12px;cursor:pointer;border-radius:9px;font-size:13px}.logout:hover{background:#ffffff10;color:white}
        .main{min-width:0;padding:24px clamp(18px,3vw,42px) 48px}.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px}
        .breadcrumb{font-size:12px;color:#8a91a4}.profile{display:flex;align-items:center;gap:10px}.profile-copy{font-size:12px;font-weight:700}.profile-copy small{display:block;color:#8a91a4;font-weight:400;margin-top:4px}
        .avatar{width:40px;height:40px;border-radius:14px;display:grid;place-items:center;background:#e9e7ff;color:#5e51d9;font-weight:800}
        .page-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:15px;margin-bottom:22px}.page-heading h1{font-size:25px;margin:0 0 7px;letter-spacing:-.5px}.page-heading p{margin:0;color:#8a91a4;font-size:12px}
        .button{border:0;border-radius:9px;background:#6558df;color:white;padding:11px 16px;font-weight:700;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:7px;transition:.15s}
        .button:hover{background:#5144c9;transform:translateY(-1px)}.button.soft{background:#eeedff;color:#5d51d4}.button.danger{background:#fff0f0;color:#d34c5b}.button.small{font-size:10px;padding:8px 10px}
        .card{background:#fff;border:1px solid #edf0f5;border-radius:14px;box-shadow:0 4px 18px #2c37500a}.stats-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:16px}
        .stat-card{padding:18px 19px;min-height:136px;position:relative;overflow:hidden}.stat-card:after{content:"";position:absolute;width:74px;height:74px;border-radius:50%;left:-24px;bottom:-29px;background:#f1efff}
        .stat-label{color:#8a91a4;font-size:11px;display:flex;align-items:center;justify-content:space-between}.stat-icon{width:32px;height:32px;border-radius:10px;display:grid;place-items:center;background:#f1efff;color:#6357db;font-size:15px}
        .stat-value{font-size:24px;font-weight:800;margin:15px 0 7px;letter-spacing:-.5px}.stat-foot{font-size:10px;color:#9aa0b0}.stat-foot strong{color:#2c9b73}
        .content-grid{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(260px,1fr);gap:15px;margin-bottom:16px}.card-heading{padding:18px 20px 10px;display:flex;justify-content:space-between;align-items:center;gap:10px}
        .card-heading h2{font-size:14px;margin:0}.card-heading p{font-size:10px;color:#9aa0b0;margin:5px 0 0}.chart-wrap{padding:8px 18px 16px}
        .chart{width:100%;height:220px;overflow:visible}.chart-grid{stroke:#edf0f5;stroke-width:1}.chart-line{fill:none;stroke:#675be0;stroke-width:3;stroke-linecap:round;stroke-linejoin:round}.chart-area{fill:url(#chartFill)}
        .chart-dot{fill:#fff;stroke:#675be0;stroke-width:3}.chart-labels{display:flex;justify-content:space-between;color:#9aa0b0;font-size:10px;margin-top:-3px}
        .status-list{padding:12px 20px 20px;display:grid;gap:16px}.status-row{display:flex;align-items:center;justify-content:space-between;font-size:11px}.status-name{display:flex;align-items:center;gap:9px;color:#747d92}.status-count{font-weight:800;font-size:13px}
        .dot{width:8px;height:8px;border-radius:50%;display:inline-block}.dot.green{background:#35b887}.dot.amber{background:#f4ae47}.dot.red{background:#eb6878}.dot.blue{background:#6b60e3}
        .progress{height:6px;background:#f0f2f7;border-radius:99px;margin-top:8px;overflow:hidden}.progress span{height:100%;display:block;border-radius:99px;background:#6c60e3}
        .filter-bar{display:flex;align-items:center;gap:9px;flex-wrap:wrap}.filter-bar input,.filter-bar select,.task-form input,.task-form textarea,.task-form select{border:1px solid #e8ebf2;background:#fff;border-radius:8px;padding:10px 11px;color:#394258;font-size:11px}
        .filter-bar label{font-size:10px;color:#8790a3}.filter-field{display:flex;align-items:center;gap:7px}.table-wrap{overflow:auto}
        table{width:100%;border-collapse:collapse;white-space:nowrap}th{font-size:10px;color:#9aa0b0;text-align:left;font-weight:600;background:#fbfcfe;padding:13px 18px;border-bottom:1px solid #f0f2f6}
        td{padding:14px 18px;border-bottom:1px solid #f2f3f7;font-size:11px;color:#596277}tbody tr:last-child td{border-bottom:0}.table-primary{font-weight:700;color:#313a50}.table-sub{display:block;color:#9aa0b0;font-size:9px;margin-top:4px}
        .badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 9px;font-size:9px;font-weight:700}.badge.completed{background:#e8f8f1;color:#248c66}.badge.pending{background:#fff5e3;color:#b88020}.badge.processing,.badge.in_progress{background:#eeedff;color:#5b50d0}.badge.cancelled{background:#fff0f1;color:#cf4f5c}
        .lower-grid{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(280px,1fr);gap:15px}.product-row{display:grid;grid-template-columns:35px minmax(0,1fr) 75px;align-items:center;gap:10px;padding:13px 19px;border-top:1px solid #f2f3f7}
        .product-art{width:34px;height:34px;border-radius:11px;background:#f0efff;color:#675be0;display:grid;place-items:center;font-size:13px}.product-name{font-size:11px;font-weight:700}.product-name small{display:block;color:#9aa0b0;font-size:9px;margin-top:4px}
        .product-sales{text-align:right;font-size:10px;color:#777f92}.product-sales strong{display:block;color:#313a50;margin-bottom:4px}
        .task-form{padding:18px;display:grid;gap:10px}.task-form input,.task-form textarea{width:100%}.task-form textarea{resize:vertical;min-height:65px}.task-form-row{display:grid;grid-template-columns:1fr auto;gap:8px}
        .task-item{padding:15px 18px;border-top:1px solid #f2f3f7;display:flex;align-items:center;justify-content:space-between;gap:14px}.task-detail{min-width:0}.task-title{font-size:11px;font-weight:700;color:#313a50}.task-description{font-size:10px;color:#9098aa;margin-top:5px;max-width:530px;overflow:hidden;text-overflow:ellipsis}
        .task-meta{font-size:9px;color:#9aa0b0;margin-top:7px}.task-actions{display:flex;align-items:center;gap:7px}.task-actions select{border:1px solid #e8ebf2;background:#fff;border-radius:7px;padding:7px;font-size:10px;max-width:125px}
        .task-edit{position:relative}.task-edit summary{list-style:none;cursor:pointer;border-radius:7px;background:#f3f2ff;color:#5d51d4;padding:8px 10px;font-size:10px;font-weight:700}.task-edit summary::-webkit-details-marker{display:none}
        .task-edit[open] summary{margin-bottom:8px}.task-edit-form{position:absolute;z-index:2;left:0;top:34px;width:min(310px,80vw);padding:12px;background:white;border:1px solid #e8ebf2;border-radius:10px;box-shadow:0 12px 30px #28324c20;display:grid;gap:8px}
        .task-edit-form input,.task-edit-form textarea,.task-edit-form select{width:100%;border:1px solid #e8ebf2;border-radius:7px;padding:8px;font-size:10px;background:white}.task-edit-form textarea{resize:vertical;min-height:56px}
        .section-gap{margin-top:16px}.empty{padding:28px;text-align:center;color:#9aa0b0;font-size:12px}.flash{padding:12px 15px;border-radius:9px;margin-bottom:15px;background:#e8f8f1;color:#248c66;font-size:12px}
        .error-list{padding:10px 15px;border-radius:9px;margin-bottom:15px;background:#fff0f1;color:#c34755;font-size:11px}
        .table-actions{display:flex;gap:7px;align-items:center}.table-actions select{border:1px solid #e8ebf2;border-radius:7px;background:white;padding:7px;font-size:10px}
        .orders-chart-line{stroke:#35b887}.monthly-chart-line{stroke:#ef9c43}.monthly-chart-dot{stroke:#ef9c43}
        .comparison-card{padding:0 0 18px}.comparison-values{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;padding:8px 20px 4px}
        .comparison-values>div{display:grid;gap:7px}.comparison-values span{font-size:10px;color:#8a91a4}.comparison-values strong{font-size:16px}.comparison-values .positive{color:#248c66}.comparison-values .negative{color:#cf4f5c}
        .order-details{position:relative}.order-details summary{list-style:none;cursor:pointer}.order-details summary::-webkit-details-marker{display:none}
        .order-items{position:absolute;z-index:3;top:34px;left:0;min-width:260px;max-width:360px;padding:10px 12px;background:#fff;border:1px solid #e8ebf2;border-radius:10px;box-shadow:0 12px 30px #28324c20;white-space:normal}
        .order-items>div{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px solid #f2f3f7;font-size:10px}.order-items>div:last-child{border-bottom:0}
        .order-items small{display:block;color:#9aa0b0;margin-top:3px}.order-items strong{white-space:nowrap}.order-status-form{display:flex;align-items:center;gap:6px}.order-status-form select{border:1px solid #e8ebf2;border-radius:7px;background:white;padding:7px;font-size:10px}
        .pagination-wrap{padding:15px 18px}
        .order-action-buttons{display:flex;align-items:center;gap:7px}.order-edit{position:relative}.order-edit summary{list-style:none;cursor:pointer}.order-edit summary::-webkit-details-marker{display:none}
        .product-actions{display:flex;align-items:center;gap:7px}.product-actions form{margin:0}
        .order-edit[open] .order-edit-form{display:grid}.order-edit-form{position:absolute;z-index:4;right:0;top:34px;width:min(380px,85vw);max-height:70vh;overflow:auto;gap:10px;padding:14px;background:white;border:1px solid #e8ebf2;border-radius:10px;box-shadow:0 12px 30px #28324c20}
        .order-edit-form label,.order-create-form label{display:grid;gap:5px;color:#8790a3;font-size:10px}.order-edit-form select,.order-edit-form input,.order-create-form select,.order-create-form input{width:100%;border:1px solid #e8ebf2;border-radius:7px;background:white;padding:9px;color:#394258;font-size:11px}
        .order-dialog{width:min(760px,calc(100% - 32px));max-width:none;max-height:calc(100% - 32px);padding:0;border:0;border-radius:20px;background:transparent;overflow:visible}
        .order-dialog::backdrop{background:#11172bb8;backdrop-filter:blur(5px)}
        .order-dialog-panel{max-height:calc(100vh - 32px);overflow:auto;border:1px solid #e8ebf3;border-radius:20px;box-shadow:0 28px 90px #11172b45}
        .order-dialog-panel>.card-heading{padding:23px 26px 19px;border-bottom:1px solid #eff1f6;background:linear-gradient(135deg,#fff 0%,#f8f7ff 100%)}
        .order-dialog-panel>.card-heading h2{font-size:18px;letter-spacing:-.3px}.order-dialog-panel>.card-heading p{font-size:11px;margin-top:7px}
        .order-create-form{padding:23px 26px 26px;display:grid;gap:20px}.order-create-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
        .order-create-form label,.product-form label{display:grid;gap:8px;color:#596277;font-size:11px;font-weight:700}
        .order-create-form select,.order-create-form input,.product-form input{width:100%;min-height:43px;border:1px solid #e5e8f0;border-radius:10px;background:#fff;padding:11px 12px;color:#313a50;font-size:12px;transition:border-color .15s,box-shadow .15s}
        .order-create-form select:focus,.order-create-form input:focus,.product-form input:focus{outline:none;border-color:#8176ea;box-shadow:0 0 0 3px #6c60e01a}
        .order-edit-items{display:grid;gap:9px;padding:14px;border:1px solid #eceef5;border-radius:13px;background:#fafbfe}
        .order-edit-item{display:grid;grid-template-columns:minmax(0,1fr) 100px 38px;gap:9px;align-items:center;padding:9px;background:#fff;border:1px solid #edf0f5;border-radius:10px}
        .order-edit-item select,.order-edit-item input{width:100%;min-height:40px;border:1px solid #e5e8f0;border-radius:8px;padding:9px 10px;background:white;color:#394258;font-size:11px}
        .order-edit-item button{width:36px;height:36px;padding:0;font-size:17px}
        .order-create-actions,.product-form-actions{display:flex;justify-content:space-between;align-items:center;gap:10px;padding-top:4px}
        .product-form{padding:23px 26px 26px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px}
        .product-form-actions{grid-column:1/-1;justify-content:flex-end;margin-top:5px;padding-top:18px;border-top:1px solid #eff1f6}
        .product-form-actions .button{min-width:120px;min-height:40px}
        .customer-mode{display:flex;flex-wrap:wrap;gap:10px 18px;margin:0;padding:13px 15px;border:1px solid #eceef5;border-radius:12px;background:#fafbfe}
        .customer-mode legend{padding:0 5px;color:#596277;font-size:11px;font-weight:700}
        .customer-mode label{display:flex;align-items:center;gap:7px;color:#596277;font-size:11px;cursor:pointer}
        .customer-mode input{width:15px;height:15px;accent-color:#6558df}
        .order-create-fields[hidden]{display:none}
        .form-hint{margin:0;color:#7a8296;font-size:11px}
        .product-actions .button:disabled{cursor:not-allowed;opacity:.65;transform:none}
        @media(max-width:1050px){.stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.content-grid,.lower-grid{grid-template-columns:1fr}}
        @media(max-width:720px){.shell{grid-template-columns:1fr}.sidebar{padding:12px 14px;gap:10px}.brand{padding:0 5px}.nav-label,.sidebar-bottom{display:none}.nav-list{display:flex;overflow:auto}.nav-link{white-space:nowrap;padding:9px 10px}.main{padding:20px 14px 35px}.topbar{margin-bottom:22px}.page-heading{align-items:flex-start;flex-direction:column}.filter-bar{align-items:stretch}.filter-field{justify-content:space-between}.filter-field input,.filter-field select{flex:1;min-width:0}.filter-bar .button{width:100%}}
        @media(max-width:600px){.comparison-values{grid-template-columns:1fr}.order-status-form{align-items:flex-start;flex-direction:column}.order-items{position:fixed;left:10px;right:10px;top:30%;max-width:none}.order-create-fields,.product-form{grid-template-columns:1fr}.order-dialog{width:calc(100% - 20px);max-height:calc(100% - 20px)}.order-dialog-panel{max-height:calc(100vh - 20px);border-radius:16px}.order-dialog-panel>.card-heading{padding:18px}.order-create-form,.product-form{padding:18px;gap:14px}.order-edit-item{grid-template-columns:minmax(0,1fr) 70px 36px}.product-form-actions{grid-column:auto}.order-edit-form{position:fixed;left:10px;right:10px;top:15%;width:auto;max-height:75vh}}
        @media(max-width:430px){.stats-grid{gap:9px}.stat-card{padding:14px;min-height:122px}.stat-value{font-size:20px}.task-item{align-items:flex-start;flex-direction:column}.task-actions{width:100%}}
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}">
            <span class="brand-mark">N</span>
            <span>NOVA<small>COMMERCE SUITE</small></span>
        </a>
        <nav>
            <div class="nav-label">WORKSPACE</div>
            <div class="nav-list">
                <a class="nav-link {{ request('tab', 'overview') === 'overview' ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-icon">◫</span>Overview</a>
                <a class="nav-link {{ request('tab') === 'tasks' ? 'active' : '' }}" href="{{ route('dashboard', ['tab' => 'tasks']) }}"><span class="nav-icon">✓</span>Tasks</a>
                @if (in_array(auth()->user()->role, ['admin', 'manager'], true))
                    <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}"><span class="nav-icon">▤</span>Orders</a>
                    <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><span class="nav-icon">◇</span>Products</a>
                    <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><span class="nav-icon">♙</span>Customers</a>
                @endif
                @if (auth()->user()->role === 'admin')
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span class="nav-icon">♙</span>User management</a>
                @endif
            </div>
        </nav>
        <div class="sidebar-bottom">
            <span class="role-chip">{{ ['admin' => 'Administrator', 'manager' => 'Manager', 'user' => 'User'][auth()->user()->role] ?? 'User' }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout" type="submit">↪ &nbsp; Sign out</button>
            </form>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <span class="breadcrumb">NOVA &nbsp; / &nbsp; @yield('breadcrumb', 'Dashboard')</span>
            <div class="profile">
                <div class="profile-copy">{{ auth()->user()->name }}<small>{{ auth()->user()->email }}</small></div>
                <span class="avatar">N</span>
            </div>
        </header>
        @if (session('success'))<div class="flash">{{ session('success') }}</div>@endif
        @if ($errors->any())
            <div class="error-list"><strong>Please review the following:</strong><ul style="margin:7px 0 0;padding-left:18px">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
