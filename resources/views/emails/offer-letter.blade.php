@component('mail::message')
# Job Offer from {{ $offer->company->name ?? 'Company' }}

Dear {{ $offer->candidate->name ?? 'Candidate' }},

We are excited to extend you an offer for the position of **{{ $offer->job_title }}**!

@if($customMessage)
---
{{ $customMessage }}
---
@endif

## Position Details

| | |
|---|---|
| **Job Title** | {{ $offer->job_title }} |
| **Department** | {{ $offer->department ?? 'N/A' }} |
| **Employment Type** | {{ ucfirst($offer->employment_type) }} |
| **Work Arrangement** | {{ ucfirst($offer->work_arrangement) }} |
| **Start Date** | {{ $offer->start_date?->format('F j, Y') }} |

## Compensation

| | |
|---|---|
| **Base Salary** | {{ $offer->formatted_salary }} |
@if($offer->signing_bonus)
| **Signing Bonus** | ${{ number_format($offer->signing_bonus, 0) }} |
@endif
@if($offer->annual_bonus_target)
| **Annual Bonus Target** | {{ $offer->annual_bonus_target }}% |
@endif
| **Total Compensation** | ${{ number_format($offer->total_compensation, 0) }}/year |

@if($offer->equity_shares)
## Equity

You will receive **{{ number_format($offer->equity_shares) }} shares** of {{ $offer->equity_type ?? 'company equity' }}.
@if($offer->vesting_schedule)
Vesting Schedule: {{ $offer->vesting_schedule }}
@endif
@endif

@if($offer->benefitsPackage)
## Benefits Package

{{ $offer->benefitsPackage->name }} (Estimated Annual Value: ${{ number_format($offer->benefitsPackage->total_value, 0) }})
@endif

---

## Important Dates

⚠️ **This offer expires on {{ $offer->offer_expiry_date?->format('F j, Y') }}**

Please review the attached offer letter carefully and respond before the expiry date.

@component('mail::button', ['url' => route('offer-letters.show', $offer)])
View Full Offer Letter
@endcomponent

---

If you have any questions about this offer, please don't hesitate to reach out.

We look forward to welcoming you to our team!

Best regards,<br>
{{ $offer->creator->name ?? 'Hiring Team' }}<br>
{{ $offer->company->name ?? config('app.name') }}

@component('mail::subcopy')
This email contains confidential information intended for {{ $offer->candidate->email }}. If you received this email in error, please delete it.
@endcomponent
@endcomponent
