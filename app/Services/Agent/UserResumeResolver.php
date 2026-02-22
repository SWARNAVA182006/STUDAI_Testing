<?php

namespace App\Services\Agent;

use App\Models\ApplicationTemplate;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UserResumeResolver
{
    /**
     * Resolve a base resume string for the given user.
     */
    public function resolve(User $user): string
    {
        $template = ApplicationTemplate::query()
            ->resumes()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('success_rate')
            ->orderByDesc('average_match_score')
            ->first();

        if ($template && trim($template->content) !== '') {
            return trim($template->content);
        }

        $profile = method_exists($user, 'profile') ? $user->profile : null;

        if ($profile) {
            $fromProfile = $this->buildFromProfile($profile, $user);
            if (trim($fromProfile) !== '') {
                return $fromProfile;
            }
        }

        return $this->fallback($user);
    }

    protected function buildFromProfile(mixed $profile, User $user): string
    {
        $headline = data_get($profile, 'headline') ?? data_get($profile, 'current_role') ?? $user->name;
        $summary = data_get($profile, 'summary');
        $experience = Arr::wrap(data_get($profile, 'experience', []));
        $education = Arr::wrap(data_get($profile, 'education', []));
        $skills = Arr::wrap(data_get($profile, 'skills', []));

        $lines = [];
        if ($headline) {
            $lines[] = Str::upper($headline);
        }
        if ($summary) {
            $lines[] = $summary;
            $lines[] = '';
        }

        if (!empty($experience)) {
            $lines[] = 'EXPERIENCE';
            foreach ($experience as $role) {
                $title = data_get($role, 'title', 'Role');
                $company = data_get($role, 'company', data_get($role, 'company_name', 'Company'));
                $period = trim((data_get($role, 'start', '') . ' - ' . data_get($role, 'end', 'Present')));
                $lines[] = Str::upper($title . ' | ' . $company);
                if ($period !== ' - Present') {
                    $lines[] = $period;
                }
                foreach (Arr::wrap(data_get($role, 'highlights', data_get($role, 'achievements', []))) as $bullet) {
                    $lines[] = '• ' . $bullet;
                }
                $lines[] = '';
            }
        }

        if (!empty($skills)) {
            $flattened = $this->flattenSkills($skills);
            if (!empty($flattened)) {
                $lines[] = 'SKILLS';
                $lines[] = implode(', ', $flattened);
                $lines[] = '';
            }
        }

        if (!empty($education)) {
            $lines[] = 'EDUCATION';
            foreach ($education as $school) {
                $institution = data_get($school, 'institution', data_get($school, 'school', 'Institution'));
                $degree = data_get($school, 'degree', data_get($school, 'qualification'));
                $year = data_get($school, 'year', data_get($school, 'graduated'));
                $line = $institution;
                if ($degree) {
                    $line .= ' - ' . $degree;
                }
                if ($year) {
                    $line .= ' (' . $year . ')';
                }
                $lines[] = $line;
            }
        }

        return trim(implode("\n", array_filter($lines, fn ($line) => $line !== null)));
    }

    protected function flattenSkills(array $skills): array
    {
        $result = [];
        foreach ($skills as $entry) {
            if (is_array($entry)) {
                $result = array_merge($result, $this->flattenSkills($entry));
            } elseif (is_string($entry)) {
                $result = array_merge($result, array_map('trim', explode(',', $entry)));
            }
        }

        $result = array_filter(array_unique($result));

        return array_values($result);
    }

    protected function fallback(User $user): string
    {
        $name = $user->name ?? 'Experienced Professional';

        return implode("\n", [
            Str::upper($name),
            'SUMMARY',
            'Adaptable professional with a track record of delivering results across cross-functional initiatives.',
            '',
            'KEY STRENGTHS',
            '• Leadership and stakeholder communication',
            '• Process optimisation and continuous improvement',
            '• Data-driven decision making',
            '',
            'EXPERIENCE',
            '• Delivered projects end-to-end, coordinating with engineering, product, and design teams.',
            '• Improved operational efficiency by introducing lightweight automation.',
            '• Partnered with leadership to translate strategic goals into execution plans.',
        ]);
    }
}
