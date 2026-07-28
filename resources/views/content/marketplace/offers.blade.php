@php
$pageConfigs = ['myLayout' => 'blank', 'hasCustomizer' => false];
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/marketplaceLayout')

@section('full-title', $domain->name . ' — Offers received')
@section('meta-description', 'Offers received for ' . $domain->name . '.')
@section('robots', 'index, follow')

@section('page-style')
<style>
  .dm-page { background: #f5f5f9; min-height: 100vh; padding: 3rem 1.5rem; }
  .dm-page__inner { max-width: 900px; margin: 0 auto; }

  @media (max-width: 768px) {
    .dm-page { padding-bottom: calc(var(--dm-bottom-nav-height) + 1.5rem); }
  }
</style>
@endsection

@section('content')
<div class="dm-page">
  <div class="dm-page__inner">
    <div class="mb-6">
      <div class="text-body-secondary text-uppercase small fw-semibold mb-1">{{ $domain->name }}</div>
      <h1 class="mb-0" style="font-size: 1.75rem;">Offers received</h1>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>From</th>
              <th>Offer</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($offers as $offer)
              <tr>
                <td>{{ $offer->masked_name }}</td>
                <td>{{ $offer->currency }} {{ number_format($offer->amount, 0) }}</td>
                <td>{{ $offer->created_at->format('M d, Y') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="text-center text-body py-8">No offers yet — be the first to make one.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
