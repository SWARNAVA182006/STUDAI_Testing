<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        $appliedAt = Carbon::instance($this->faker->dateTimeBetween('-2 months', '-1 week'));
        $viewedAt = $this->faker->boolean(60) ? Carbon::instance($this->faker->dateTimeBetween($appliedAt, 'now')) : null;
        $respondedAt = $viewedAt && $this->faker->boolean(55)
            ? Carbon::instance($this->faker->dateTimeBetween($viewedAt, 'now'))
            : null;
        $interviewAt = $respondedAt && $this->faker->boolean(50)
            ? Carbon::instance($this->faker->dateTimeBetween($respondedAt, '+2 weeks'))
            : null;
        $decisionAt = $interviewAt && $this->faker->boolean(45)
            ? Carbon::instance($this->faker->dateTimeBetween($interviewAt, '+3 weeks'))
            : null;

        $statusOptions = [
            Application::STATUS_SUBMITTED,
            Application::STATUS_VIEWED,
            Application::STATUS_SHORTLISTED,
            Application::STATUS_INTERVIEW_SCHEDULED,
            Application::STATUS_INTERVIEW_COMPLETED,
            Application::STATUS_OFFER_EXTENDED,
            Application::STATUS_ACCEPTED,
            Application::STATUS_REJECTED,
            Application::STATUS_WITHDRAWN,
        ];

        $status = $this->faker->randomElement($statusOptions);

        return [
            'user_id' => User::factory(),
            'job_id' => Job::factory(),
            'status' => $status,
            'custom_resume' => $this->faker->optional()->url(),
            'custom_cover_letter' => $this->faker->optional()->paragraphs(2, true),
            'answers' => [
                'strengths' => $this->faker->sentences(2, true),
                'motivation' => $this->faker->sentence(),
                'years_experience' => $this->faker->numberBetween(1, 12),
            ],
            'notes' => $this->faker->optional()->sentences(2, true),
            'applied_at' => $appliedAt,
            'viewed_at' => $viewedAt,
            'responded_at' => $respondedAt,
            'interview_at' => $interviewAt,
            'offer_at' => in_array($status, [Application::STATUS_OFFER_EXTENDED, Application::STATUS_ACCEPTED]) ? $decisionAt : null,
            'decision_at' => in_array($status, [Application::STATUS_ACCEPTED, Application::STATUS_REJECTED, Application::STATUS_WITHDRAWN]) ? $decisionAt : null,
        ];
    }

    public function submitted(): self
    {
        return $this->state(fn () => [
            'status' => Application::STATUS_SUBMITTED,
            'viewed_at' => null,
            'responded_at' => null,
            'interview_at' => null,
            'offer_at' => null,
            'decision_at' => null,
        ]);
    }

    public function accepted(): self
    {
        return $this->state(function () {
            $appliedAt = Carbon::now()->subWeeks(3);
            $interviewAt = $appliedAt->copy()->addWeek();
            $decisionAt = $interviewAt->copy()->addDays(7);

            return [
                'status' => Application::STATUS_ACCEPTED,
                'applied_at' => $appliedAt,
                'viewed_at' => $appliedAt->copy()->addDays(2),
                'responded_at' => $appliedAt->copy()->addDays(5),
                'interview_at' => $interviewAt,
                'offer_at' => $decisionAt->copy()->subDays(2),
                'decision_at' => $decisionAt,
            ];
        });
    }

    public function rejected(): self
    {
        return $this->state(function () {
            $appliedAt = Carbon::now()->subWeeks(2);

            return [
                'status' => Application::STATUS_REJECTED,
                'applied_at' => $appliedAt,
                'viewed_at' => $appliedAt->copy()->addDays(1),
                'responded_at' => $appliedAt->copy()->addDays(3),
                'decision_at' => $appliedAt->copy()->addDays(10),
            ];
        });
    }
}
