<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resume - {{ $user->name }}</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ec4899;
        }
        .name {
            font-size: 24pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .contact {
            font-size: 10pt;
            color: #6b7280;
        }
        .contact a {
            color: #ec4899;
            text-decoration: none;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .highlight {
            background-color: #fef3c7;
            padding: 2px 4px;
            border-radius: 2px;
        }
        .job-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1f2937;
        }
        .company {
            font-size: 11pt;
            color: #4b5563;
            font-weight: 600;
        }
        .duration {
            font-size: 10pt;
            color: #6b7280;
            font-style: italic;
        }
        .job-details {
            margin-top: 8px;
            margin-left: 15px;
        }
        .job-details ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .job-details li {
            margin-bottom: 5px;
        }
        .skills-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .skill-tag {
            display: inline-block;
            background-color: #dbeafe;
            color: #1e40af;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: 600;
        }
        .skill-tag.highlight {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
        }
        .education-item {
            margin-bottom: 15px;
        }
        .degree {
            font-size: 11pt;
            font-weight: bold;
            color: #1f2937;
        }
        .institution {
            font-size: 10pt;
            color: #4b5563;
        }
        .summary {
            text-align: justify;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .ai-notice {
            font-size: 8pt;
            color: #9ca3af;
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div class="name">{{ $user->name }}</div>
        <div class="contact">
            @if($user->email)
                <a href="mailto:{{ $user->email }}">{{ $user->email }}</a> •
            @endif
            @if($profile->phone)
                {{ $profile->phone }} •
            @endif
            @if($profile->location)
                {{ $profile->location }}
            @endif
            @if($profile->linkedin_url)
                <br><a href="{{ $profile->linkedin_url }}">LinkedIn Profile</a>
            @endif
            @if($profile->portfolio_url)
                • <a href="{{ $profile->portfolio_url }}">Portfolio</a>
            @endif
        </div>
    </div>

    {{-- Professional Summary --}}
    @if($customizedSummary)
        <div class="section">
            <div class="section-title">Professional Summary</div>
            <div class="summary">
                {!! nl2br(e($customizedSummary)) !!}
            </div>
        </div>
    @endif

    {{-- Skills --}}
    @if($highlightedSkills || $profile->skills)
        <div class="section">
            <div class="section-title">Skills</div>
            <div class="skills-grid">
                @foreach($highlightedSkills as $skill)
                    <span class="skill-tag highlight">{{ $skill }}</span>
                @endforeach
                @foreach($profile->skills as $skill)
                    @if(!in_array($skill, $highlightedSkills))
                        <span class="skill-tag">{{ $skill }}</span>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Experience --}}
    @if($customizedExperience && count($customizedExperience) > 0)
        <div class="section">
            <div class="section-title">Professional Experience</div>
            @foreach($customizedExperience as $exp)
                <div style="margin-bottom: 20px;">
                    <div class="job-title">{{ $exp['title'] }}</div>
                    <div class="company">{{ $exp['company'] }}</div>
                    <div class="duration">
                        {{ date('M Y', strtotime($exp['start_date'])) }} - 
                        {{ $exp['end_date'] ? date('M Y', strtotime($exp['end_date'])) : 'Present' }}
                        @if($exp['location'])
                            • {{ $exp['location'] }}
                        @endif
                    </div>
                    <div class="job-details">
                        {!! $exp['description_html'] !!}
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Education --}}
    @if($profile->education && count($profile->education) > 0)
        <div class="section">
            <div class="section-title">Education</div>
            @foreach($profile->education as $edu)
                <div class="education-item">
                    <div class="degree">{{ $edu['degree'] ?? '' }} {{ $edu['field_of_study'] ?? '' }}</div>
                    <div class="institution">{{ $edu['institution'] ?? '' }}</div>
                    @if(isset($edu['graduation_year']))
                        <div class="duration">Graduated {{ $edu['graduation_year'] }}</div>
                    @endif
                    @if(isset($edu['gpa']))
                        <div style="font-size: 10pt; color: #6b7280;">GPA: {{ $edu['gpa'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Certifications --}}
    @if(isset($profile->certifications) && count($profile->certifications) > 0)
        <div class="section">
            <div class="section-title">Certifications</div>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($profile->certifications as $cert)
                    <li>
                        <strong>{{ $cert['name'] ?? '' }}</strong>
                        @if(isset($cert['issuer']))
                            - {{ $cert['issuer'] }}
                        @endif
                        @if(isset($cert['date']))
                            ({{ date('Y', strtotime($cert['date'])) }})
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Projects (if relevant) --}}
    @if(isset($profile->projects) && count($profile->projects) > 0)
        <div class="section">
            <div class="section-title">Key Projects</div>
            @foreach(array_slice($profile->projects, 0, 3) as $project)
                <div style="margin-bottom: 15px;">
                    <div style="font-weight: bold; color: #1f2937;">{{ $project['name'] ?? '' }}</div>
                    @if(isset($project['description']))
                        <div style="font-size: 10pt; color: #4b5563; margin-top: 3px;">
                            {{ $project['description'] }}
                        </div>
                    @endif
                    @if(isset($project['technologies']))
                        <div style="font-size: 9pt; color: #6b7280; margin-top: 3px;">
                            <em>Technologies:</em> {{ implode(', ', $project['technologies']) }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- AI Customization Notice --}}
    <div class="ai-notice">
        This resume was automatically customized for {{ $jobTitle }} at {{ $companyName }} using AI-powered optimization.
        <br>Generated on {{ date('F j, Y') }} by StudAI Career Autonomous Agent
    </div>
</body>
</html>
