@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
🏢 {{ config('app.name') }}
@endcomponent
@endslot

# 🎉 Welcome to {{ $company->getTranslation('name', 'en') }}!

Hello **{{ $notifiable->name }}**,

Your subscription has been created successfully! We're excited to have you on board.

---

## 📋 Subscription Summary

@component('mail::table')
| **Detail** | **Information** |
|------------|-----------------|
| 🏢 **Company** | {{ $company->getTranslation('name', 'en') }} |
| 📌 **Plan** | {{ $plan->getTranslation('name', 'en') }} |
| 💰 **Price** | ${{ number_format($price->price, 2) }} / {{ $price->interval->value }} |
| 🎁 **Trial** | {{ $subscription->trial_end_date ? \Carbon\Carbon::parse($subscription->trial_end_date)->format('d F Y') : 'No Trial' }} |
| 📊 **Status** | {{ ucfirst($subscription->status->value) }} |
@endcomponent

---

## 🚀 Next Steps

@component('mail::button', ['url' => $checkoutUrl, 'color' => 'success'])
💳 Complete Payment
@endcomponent

@component('mail::button', ['url' => url("/api/v1/central/subscriptions/{$subscription->id}"), 'color' => 'blue'])
🔍 View Details
@endcomponent

---

## 🔗 Quick Links

- **💳 Pay Now:** [{{ $checkoutUrl }}]({{ $checkoutUrl }})
- **🔍 View Subscription:** [{{ url("/api/v1/central/subscriptions/{$subscription->id}") }}]({{ url("/api/v1/central/subscriptions/{$subscription->id}") }})

---

## 📧 Need Help?

If you have any questions, our support team is here to help.

---

**Thank you for choosing {{ config('app.name') }}!** 🚀

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
@endcomponent
@endslot

@endcomponent
