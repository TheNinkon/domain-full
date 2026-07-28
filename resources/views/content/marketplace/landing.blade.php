@php
$pageConfigs = ['myLayout' => 'blank', 'hasCustomizer' => false];
$customizerHidden = 'customizer-hide';
$shouldOpenOfferModal = $offerSubmitted || count($formErrors) > 0;
@endphp

@extends('layouts/marketplaceLayout')

@section('full-title', $domain->seo_title ?: ($domain->name . ' is for sale'))
@section('meta-description', $domain->seo_description ?: 'This premium domain name is available for purchase. Make an offer today.')
@section('robots', 'index, follow')
@section('canonical', request()->getScheme() . '://' . $domain->name . '/')

@section('vendor-script')
@if ($captchaSiteKey)
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
@endsection

@section('page-style')
<style>
  html, body { height: 100%; overflow: hidden; }

  .dm-hero {
    position: relative;
    height: 100vh;
    overflow: hidden;
    display: flex;
    align-items: center;
    background:
      radial-gradient(ellipse 60% 50% at 15% 10%, rgba(105, 108, 255, .35) 0%, transparent 60%),
      radial-gradient(ellipse 50% 40% at 90% 85%, rgba(143, 146, 255, .18) 0%, transparent 60%),
      radial-gradient(circle at top left, #3a3a7a 0%, #1c1c3a 45%, #101022 100%);
    padding: 3rem 1.5rem;
  }
  .dm-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
      linear-gradient(rgba(255, 255, 255, .035) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255, 255, 255, .035) 1px, transparent 1px);
    background-size: 44px 44px;
    mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000 0%, transparent 75%);
    pointer-events: none;
  }
  .dm-hero__inner { position: relative; z-index: 1; }

  @media (max-width: 768px) {
    .dm-hero {
      height: calc(100vh - var(--dm-bottom-nav-height));
      padding: 1.5rem 1.25rem;
    }
    .dm-hero__eyebrow { font-size: .68rem; letter-spacing: .14em; }
    .dm-hero__domain { font-size: clamp(1.75rem, 8vw, 2.5rem); line-height: 1.15; }
    .dm-hero__lead { font-size: .95rem; }
    .dm-hero__lead.mb-6 { margin-bottom: 1.25rem !important; }

    .d-flex.mb-8 { margin-bottom: 1.5rem !important; }
    .dm-stat {
      min-width: 0;
      flex: 1 1 calc(50% - .5rem);
      padding: .7rem .85rem;
      gap: .6rem;
      border-radius: .75rem;
    }
    .dm-stat__icon { width: 1.85rem; height: 1.85rem; font-size: .9rem; border-radius: .5rem; }
    .dm-stat__value { font-size: 1.05rem; }
    .dm-stat__label { font-size: .68rem; }

    .dm-cta.btn-lg { padding: .7rem 1.75rem; font-size: .95rem; }

    #offerModal .modal-body { padding: 1rem 1.25rem; }
    #offerModal .modal-header,
    #offerModal .modal-footer { padding: .85rem 1.25rem; }
    #offerModal .form-label { font-size: .85rem; margin-bottom: .3rem; }
    #offerModal .mb-4 { margin-bottom: .85rem !important; }
  }

  .dm-hero__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    letter-spacing: .18em;
    text-transform: uppercase;
    font-size: .78rem;
    font-weight: 600;
    color: #b8bcff;
  }
  .dm-hero__eyebrow::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #8f92ff;
    box-shadow: 0 0 0 4px rgba(143, 146, 255, .2);
  }

  .dm-hero__domain {
    font-size: clamp(2.25rem, 5vw, 4.25rem);
    font-weight: 800;
    line-height: 1.08;
    letter-spacing: -.02em;
    word-break: break-word;
    background: linear-gradient(180deg, #ffffff 0%, #d7d9ff 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    text-shadow: 0 2px 40px rgba(143, 146, 255, .25);
  }

  .dm-hero__lead {
    color: #c7c9e8;
    font-size: 1.125rem;
    max-width: 34rem;
  }

  .dm-stat {
    background: rgba(255, 255, 255, .05);
    border: 1px solid rgba(255, 255, 255, .1);
    border-radius: 1rem;
    padding: 1rem 1.35rem;
    min-width: 10rem;
    display: flex;
    align-items: center;
    gap: .85rem;
    backdrop-filter: blur(6px);
    transition: transform .18s ease, border-color .18s ease, background-color .18s ease;
  }
  .dm-stat:hover {
    transform: translateY(-2px);
    border-color: rgba(143, 146, 255, .35);
    background: rgba(255, 255, 255, .08);
  }
  .dm-stat__icon {
    flex-shrink: 0;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: .65rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(143, 146, 255, .16);
    color: #b8bcff;
    font-size: 1.1rem;
  }
  .dm-stat__body { text-align: left; }
  .dm-stat__value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1.2; }
  .dm-stat__label { font-size: .76rem; color: #9799c2; }

  .dm-cta {
    box-shadow: 0 12px 30px -8px rgba(105, 108, 255, .55);
    transition: transform .18s ease, box-shadow .18s ease;
  }
  .dm-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 36px -6px rgba(105, 108, 255, .65);
  }

  .dm-trust { color: #8688b8; font-size: .8rem; }

  .dm-fade-up {
    opacity: 0;
    transform: translateY(14px);
    animation: dm-fade-up .6s ease forwards;
  }
  .dm-fade-up:nth-child(1) { animation-delay: .05s; }
  .dm-fade-up:nth-child(2) { animation-delay: .15s; }
  .dm-fade-up:nth-child(3) { animation-delay: .25s; }
  .dm-fade-up:nth-child(4) { animation-delay: .35s; }
  .dm-fade-up:nth-child(5) { animation-delay: .45s; }
  @keyframes dm-fade-up {
    to { opacity: 1; transform: translateY(0); }
  }
  @media (prefers-reduced-motion: reduce) {
    .dm-fade-up { animation: none; opacity: 1; transform: none; }
  }
</style>
@endsection

@section('page-script')
@if ($shouldOpenOfferModal)
<script type="module">
  document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('offerModal')).show();
  });
</script>
@endif
@endsection

@section('content')
<div class="dm-hero">
  <div class="container-xxl dm-hero__inner">
    <div class="row justify-content-center text-center">
      <div class="col-lg-8">
        <div class="dm-hero__eyebrow mb-3 dm-fade-up">Premium domain &middot; for sale</div>
        <h1 class="dm-hero__domain mb-4 dm-fade-up">{{ $domain->name }}</h1>
        <p class="dm-hero__lead mb-6 mx-auto dm-fade-up">
          This domain is available for purchase. Make an offer and the owner will get
          back to you directly by email.
        </p>

        @if ($offerCount > 0 || $visitsThisMonth > 0)
          <div class="d-flex flex-wrap justify-content-center gap-3 mb-8 dm-fade-up">
            @if ($offerCount > 0)
              <div class="dm-stat">
                <span class="dm-stat__icon"><i class="ti tabler-message-2"></i></span>
                <span class="dm-stat__body">
                  <span class="dm-stat__value d-block">{{ $offerCount }}</span>
                  <span class="dm-stat__label">{{ $offerCount === 1 ? 'offer received' : 'offers received' }}</span>
                </span>
              </div>
            @endif
            @if ($averageOffer)
              <div class="dm-stat">
                <span class="dm-stat__icon"><i class="ti tabler-currency-dollar"></i></span>
                <span class="dm-stat__body">
                  <span class="dm-stat__value d-block">${{ number_format($averageOffer, 0) }}</span>
                  <span class="dm-stat__label">average offer</span>
                </span>
              </div>
            @endif
            @if ($visitsThisMonth > 0)
              <div class="dm-stat">
                <span class="dm-stat__icon"><i class="ti tabler-eye"></i></span>
                <span class="dm-stat__body">
                  <span class="dm-stat__value d-block">{{ $visitsThisMonth }}</span>
                  <span class="dm-stat__label">{{ $visitsThisMonth === 1 ? 'visit this month' : 'visits this month' }}</span>
                </span>
              </div>
            @endif
          </div>
        @endif

        <button type="button" class="btn btn-primary btn-lg px-8 dm-cta dm-fade-up" data-bs-toggle="modal" data-bs-target="#offerModal">
          Make an offer
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Offer modal -->
<div class="modal fade" id="offerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      @if ($offerSubmitted)
        <div class="modal-header">
          <h5 class="modal-title">Offer sent 🎉</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-body mb-0">
            Thanks! Your offer for <strong>{{ $domain->name }}</strong> was sent to the
            owner. They'll reach out to you by email if interested.
          </p>
        </div>
      @else
        <div class="modal-header">
          <h5 class="modal-title">Make an offer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-body-secondary">Get in touch about {{ $domain->name }}.</p>

          @if (count($formErrors))
            <div class="alert alert-danger" role="alert">
              <ul class="mb-0">
                @foreach ($formErrors as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form id="offerForm" method="POST" action="{{ $siteRoot }}/offers">
            <input type="text" name="website" value="" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off" />

            <div class="mb-4">
              <label class="form-label" for="name">Your name</label>
              <input type="text" class="form-control" id="name" name="name" value="{{ $old['name'] ?? '' }}" />
            </div>
            <div class="mb-4">
              <label class="form-label" for="email">Email</label>
              <input type="email" class="form-control" id="email" name="email" value="{{ $old['email'] ?? '' }}" required />
            </div>
            <div class="mb-4">
              <label class="form-label" for="phone">Phone (optional)</label>
              <input type="tel" class="form-control" id="phone" name="phone" value="{{ $old['phone'] ?? '' }}" />
            </div>
            <div class="mb-4">
              <label class="form-label" for="amount">Your offer (USD)</label>
              <input type="number" min="1" step="1" class="form-control" id="amount" name="amount"
                value="{{ $old['amount'] ?? '' }}" required />
            </div>
            <div class="mb-4">
              <label class="form-label" for="message">Message (optional)</label>
              <textarea class="form-control" id="message" name="message" rows="3">{{ $old['message'] ?? '' }}</textarea>
            </div>

            @if ($captchaSiteKey)
              <div class="mb-4">
                <div class="g-recaptcha" data-sitekey="{{ $captchaSiteKey }}"></div>
              </div>
            @endif

            <p class="dm-trust mb-0">Your contact info is only shared with the domain owner.</p>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" form="offerForm" class="btn btn-primary">Send offer</button>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
