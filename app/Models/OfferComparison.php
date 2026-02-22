<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferComparison extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'offer_ids',
        'comparison_criteria',
        'notes',
    ];

    protected $casts = [
        'offer_ids' => 'array',
        'comparison_criteria' => 'array',
        'notes' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getOffers()
    {
        return OfferLetter::whereIn('id', $this->offer_ids ?? [])->get();
    }

    public function getComparisonDataAttribute(): array
    {
        $offers = $this->getOffers();
        $comparison = [];

        foreach ($offers as $offer) {
            $comparison[] = [
                'id' => $offer->id,
                'company' => $offer->company->name ?? 'Unknown',
                'job_title' => $offer->job_title,
                'base_salary' => (float) $offer->base_salary,
                'annualized_salary' => $offer->getAnnualizedSalary(),
                'signing_bonus' => (float) ($offer->signing_bonus ?? 0),
                'annual_bonus_target' => (float) ($offer->annual_bonus_target ?? 0),
                'total_compensation' => $offer->total_compensation,
                'equity_shares' => $offer->equity_shares ?? 0,
                'equity_type' => $offer->equity_type,
                'start_date' => $offer->start_date?->format('Y-m-d'),
                'work_arrangement' => $offer->work_arrangement,
                'work_location' => $offer->work_location,
                'benefits_value' => $offer->benefitsPackage?->total_value ?? 0,
                'status' => $offer->status,
            ];
        }

        return $comparison;
    }

    public function getSalaryComparisonAttribute(): array
    {
        $offers = $this->getOffers();
        
        if ($offers->isEmpty()) {
            return [];
        }

        $salaries = $offers->pluck('base_salary')->map(fn($s) => (float) $s);
        
        return [
            'min' => $salaries->min(),
            'max' => $salaries->max(),
            'avg' => $salaries->avg(),
            'difference' => $salaries->max() - $salaries->min(),
        ];
    }

    public function getBestOfferByAttribute(string $attribute): ?OfferLetter
    {
        $offers = $this->getOffers();
        
        return match($attribute) {
            'salary' => $offers->sortByDesc('base_salary')->first(),
            'total_compensation' => $offers->sortByDesc('total_compensation')->first(),
            'equity' => $offers->sortByDesc('equity_shares')->first(),
            'earliest_start' => $offers->sortBy('start_date')->first(),
            default => null,
        };
    }

    public function addOffer(int $offerId): void
    {
        $offers = $this->offer_ids ?? [];
        if (!in_array($offerId, $offers)) {
            $offers[] = $offerId;
            $this->update(['offer_ids' => $offers]);
        }
    }

    public function removeOffer(int $offerId): void
    {
        $offers = $this->offer_ids ?? [];
        $offers = array_filter($offers, fn($id) => $id !== $offerId);
        $this->update(['offer_ids' => array_values($offers)]);
    }

    public function addNote(int $offerId, string $note): void
    {
        $notes = $this->notes ?? [];
        $notes[$offerId] = $note;
        $this->update(['notes' => $notes]);
    }

    public function getRecommendation(): array
    {
        $offers = $this->getOffers();
        
        if ($offers->isEmpty()) {
            return ['recommendation' => null, 'reasons' => []];
        }

        $scored = $offers->map(function ($offer) {
            $score = 0;
            $reasons = [];

            // Score based on salary (40%)
            $maxSalary = $this->getOffers()->max('base_salary');
            if ($maxSalary > 0) {
                $salaryScore = ((float) $offer->base_salary / (float) $maxSalary) * 40;
                $score += $salaryScore;
                if ($offer->base_salary == $maxSalary) {
                    $reasons[] = 'Highest base salary';
                }
            }

            // Score based on total compensation (30%)
            $maxComp = $this->getOffers()->max('total_compensation');
            if ($maxComp > 0) {
                $compScore = ($offer->total_compensation / $maxComp) * 30;
                $score += $compScore;
                if ($offer->total_compensation == $maxComp) {
                    $reasons[] = 'Highest total compensation';
                }
            }

            // Score based on benefits (15%)
            $benefitsValue = $offer->benefitsPackage?->total_value ?? 0;
            $maxBenefits = $this->getOffers()->max(fn($o) => $o->benefitsPackage?->total_value ?? 0);
            if ($maxBenefits > 0) {
                $benefitsScore = ($benefitsValue / $maxBenefits) * 15;
                $score += $benefitsScore;
                if ($benefitsValue == $maxBenefits) {
                    $reasons[] = 'Best benefits package';
                }
            }

            // Score based on work flexibility (15%)
            $flexScore = match($offer->work_arrangement) {
                'remote' => 15,
                'hybrid' => 10,
                'on-site' => 5,
                default => 0,
            };
            $score += $flexScore;
            if ($offer->work_arrangement === 'remote') {
                $reasons[] = 'Remote work option';
            }

            return [
                'offer' => $offer,
                'score' => $score,
                'reasons' => $reasons,
            ];
        });

        $best = $scored->sortByDesc('score')->first();

        return [
            'recommendation' => $best['offer'],
            'score' => $best['score'],
            'reasons' => $best['reasons'],
        ];
    }
}
