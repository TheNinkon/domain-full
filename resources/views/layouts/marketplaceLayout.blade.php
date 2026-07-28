@isset($pageConfigs)
  {!! Helper::updatePageConfig($pageConfigs) !!}
@endisset

@php
  $configData = Helper::appClasses();
  $customizerHidden = $customizerHidden ?? '';
@endphp

@extends('layouts/commonMaster')

@section('layoutContent')
<style>
  :root { --dm-bottom-nav-height: 64px; }

  .dm-shell { display: flex; min-height: 100vh; }
  .dm-sidebar {
    width: 76px;
    flex-shrink: 0;
    background: #16162c;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.5rem 0;
    gap: .5rem;
  }
  .dm-sidebar__brand {
    width: 44px;
    height: 44px;
    border-radius: .75rem;
    background: linear-gradient(135deg, #696cff, #8f92ff);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
    text-decoration: none;
  }
  .dm-sidebar__link {
    width: 44px;
    height: 44px;
    border-radius: .65rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #8688b8;
    font-size: 1.25rem;
    text-decoration: none;
    transition: background-color .15s ease, color .15s ease;
  }
  .dm-sidebar__link:hover {
    background: rgba(255, 255, 255, .06);
    color: #fff;
  }
  .dm-sidebar__link.active {
    background: rgba(105, 108, 255, .2);
    color: #b4b6ff;
  }
  .dm-main { flex: 1 1 auto; min-width: 0; }

  /* App-style bottom tab bar — replaces the sidebar on small screens,
     same idea as WhatsApp's Chats/Updates/Communities/Calls tabs. */
  .dm-bottom-nav {
    display: none;
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1030;
    background: #16162c;
    border-top: 1px solid rgba(255, 255, 255, .08);
    padding-bottom: env(safe-area-inset-bottom);
  }
  .dm-bottom-nav__list {
    display: flex;
    height: var(--dm-bottom-nav-height);
    margin: 0;
    padding: 0;
    list-style: none;
  }
  .dm-bottom-nav__item { flex: 1 1 0; display: flex; }
  .dm-bottom-nav__link {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .2rem;
    color: #8688b8;
    text-decoration: none;
    font-size: .68rem;
    font-weight: 500;
  }
  .dm-bottom-nav__link i { font-size: 1.35rem; }
  .dm-bottom-nav__link.active { color: #b4b6ff; }

  @media (max-width: 768px) {
    .dm-sidebar { display: none; }
    .dm-bottom-nav { display: block; }
  }
</style>

<div class="dm-shell">
  <aside class="dm-sidebar">
    <a href="{{ $siteRoot }}/" class="dm-sidebar__brand" title="{{ $domain->name }}">
      {{ mb_strtoupper(mb_substr($domain->name, 0, 1)) }}
    </a>
    <a href="{{ $siteRoot }}/" class="dm-sidebar__link {{ request()->is('/') ? 'active' : '' }}" title="Home">
      <i class="ti tabler-home"></i>
    </a>
    <a href="{{ $siteRoot }}/metrics" class="dm-sidebar__link {{ request()->is('metrics') ? 'active' : '' }}" title="Metrics">
      <i class="ti tabler-chart-bar"></i>
    </a>
    <a href="{{ $siteRoot }}/offers" class="dm-sidebar__link {{ request()->is('offers') ? 'active' : '' }}" title="Offers">
      <i class="ti tabler-tag"></i>
    </a>
  </aside>
  <div class="dm-main">
    @yield('content')
  </div>
</div>

<nav class="dm-bottom-nav">
  <ul class="dm-bottom-nav__list list-unstyled mb-0">
    <li class="dm-bottom-nav__item">
      <a href="{{ $siteRoot }}/" class="dm-bottom-nav__link {{ request()->is('/') ? 'active' : '' }}">
        <i class="ti tabler-home"></i>
        <span>Home</span>
      </a>
    </li>
    <li class="dm-bottom-nav__item">
      <a href="{{ $siteRoot }}/metrics" class="dm-bottom-nav__link {{ request()->is('metrics') ? 'active' : '' }}">
        <i class="ti tabler-chart-bar"></i>
        <span>Metrics</span>
      </a>
    </li>
    <li class="dm-bottom-nav__item">
      <a href="{{ $siteRoot }}/offers" class="dm-bottom-nav__link {{ request()->is('offers') ? 'active' : '' }}">
        <i class="ti tabler-tag"></i>
        <span>Offers</span>
      </a>
    </li>
  </ul>
</nav>
@endsection
