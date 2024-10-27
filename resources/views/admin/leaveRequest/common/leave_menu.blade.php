<div class="card overflow-hidden">
    <ul class="nav payroll-sidebar-menu">
        <li class="nav-item {{request()->routeIs('admin.leaves.*') ? 'bg-danger' : '' }} w-100" style="border-bottom: 1px solid #ede7e7;">
            <a class="nav-link {{request()->routeIs('admin.leaves.*') ? 'text-white' : 'text-black' }}"
               href="{{ route('admin.leaves.index')}}">
                {{ __('index.leave_types') }}
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('admin.leave-request.*')  ? 'bg-danger' : '' }} w-100" style="border-bottom: 1px solid #ede7e7;">
            <a class="nav-link {{ request()->routeIs('admin.leave-request.*') ? 'text-white' : 'text-black' }}"
               href="{{ route('admin.leave-request.index')}}">
                {{ __('index.leave_request') }}
            </a>
        </li>
        <li class="nav-item {{request()->routeIs('admin.time-leave-request.*')  ? 'bg-danger' : '' }} w-100" style="border-bottom: 1px solid #ede7e7;">
            <a class="nav-link {{request()->routeIs('admin.time-leave-request.*') ? 'text-white' : 'text-black' }}"
               href="{{ route('admin.time-leave-request.index')}}">
                {{ __('index.time_leave_request') }}
            </a>
        </li>
    </ul>
</div>
