{{-- Sidebar --}}
<aside class="app-sidebar" id="app-sidebar">
    <div class="sidebar-toggle" id="sidebar-toggle" title="Toggle sidebar">
        <i class="bi bi-chevron-left"></i>
    </div>

    <div class="sidebar-brand">
        <span class="sidebar-brand-icon"><img src="{{ asset('images/1-logo.png') }}" alt="Logo"></span>
        <span class="sidebar-brand-text">ក្រសួងសេដ្ឋកិច្ចនិងហិរញ្ញវត្ថុ</span>
    </div>

    <div class="sidebar-section-label">ទំព័រមុខ</div>
    <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
            <a href="{{ route('dashboard.index') }}" class="sidebar-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}" data-tooltip="ផ្ទាំងគ្រប់គ្រង">
                <span class="sidebar-link-icon"><i class="bi bi-speedometer2"></i></span>
                <span class="sidebar-link-text">ផ្ទាំងគ្រប់គ្រង</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-section-label ">គ្រប់គ្រង</div>
    <ul class="sidebar-nav mb-4">
        <li class="sidebar-nav-item">
            <a href="{{ route('civil-servants.index') }}" class="sidebar-link {{ request()->routeIs('civil-servants.*') ? 'active' : '' }}" data-tooltip="បោះពុម្ភរូបថត">
                <span class="sidebar-link-icon"><i class="bi bi-camera-fill"></i></span>
                <span class="sidebar-link-text">បោះពុម្ភរូបថត</span>
            </a>
        </li>

        <li class="sidebar-nav-item">
            <a href="{{ route('civilservant-id.index') }}" class="sidebar-link {{ request()->routeIs('civilservant-id.*') ? 'active' : '' }}" data-tooltip="អត្តលេខមន្រ្តីរាជការ">
                <span class="sidebar-link-icon"><i class="bi bi-person-badge-fill"></i></span>
                <span class="sidebar-link-text">អត្តលេខមន្រ្តីរាជការ</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <span class="sidebar-user-avatar"><i class="bi bi-person-circle"></i></span>
            <span class="sidebar-user-text">Admin</span>
        </div>
    </div>
</aside>
