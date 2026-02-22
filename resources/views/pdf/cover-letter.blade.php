<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cover Letter - {{ $user->name }}</title>
    <style>
        @page {
            margin: 3cm 2.5cm;
        }
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.8;
            color: #1f2937;
        }
        .letterhead {
            margin-bottom: 40px;
        }
        .sender-info {
            text-align: right;
            margin-bottom: 30px;
        }
        .sender-name {
            font-weight: bold;
            font-size: 12pt;
        }
        .sender-contact {
            font-size: 10pt;
            color: #4b5563;
        }
        .date {
            margin-bottom: 30px;
            font-size: 10pt;
        }
        .recipient-info {
            margin-bottom: 30px;
        }
        .recipient-name {
            font-weight: bold;
        }
        .salutation {
            margin-bottom: 20px;
        }
        .body-content {
            text-align: justify;
        }
        .paragraph {
            margin-bottom: 18px;
        }
        .closing {
            margin-top: 30px;
            margin-bottom: 50px;
        }
        .signature {
            margin-top: 10px;
            font-weight: bold;
        }
        .highlight {
            font-weight: 600;
            color: #1f2937;
        }
        .ai-notice {
            font-size: 8pt;
            color: #9ca3af;
            text-align: center;
            margin-top: 50px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    {{-- Sender Information --}}
    <div class="letterhead">
        <div class="sender-info">
            <div class="sender-name">{{ $user->name }}</div>
            <div class="sender-contact">
                @if($profile->phone)
                    {{ $profile->phone }}<br>
                @endif
                {{ $user->email }}<br>
                @if($profile->location)
                    {{ $profile->location }}<br>
                @endif
                @if($profile->linkedin_url)
                    {{ $profile->linkedin_url }}
                @endif
            </div>
        </div>

        <div class="date">{{ date('F j, Y') }}</div>

        <div class="recipient-info">
            @if($hiringManagerName)
                <div class="recipient-name">{{ $hiringManagerName }}</div>
                <div>Hiring Manager</div>
            @else
                <div class="recipient-name">Hiring Manager</div>
            @endif
            <div>{{ $companyName }}</div>
            @if($companyLocation)
                <div>{{ $companyLocation }}</div>
            @endif
        </div>
    </div>

    {{-- Salutation --}}
    <div class="salutation">
        Dear @if($hiringManagerName){{ $hiringManagerName }}@else Hiring Manager@endif,
    </div>

    {{-- Letter Body --}}
    <div class="body-content">
        {{-- Opening Paragraph --}}
        <div class="paragraph">
            {!! $openingParagraph !!}
        </div>

        {{-- Body Paragraphs --}}
        @foreach($bodyParagraphs as $paragraph)
            <div class="paragraph">
                {!! $paragraph !!}
            </div>
        @endforeach

        {{-- Closing Paragraph --}}
        <div class="paragraph">
            {!! $closingParagraph !!}
        </div>
    </div>

    {{-- Closing & Signature --}}
    <div class="closing">
        {{ $closingPhrase ?? 'Sincerely' }},
        <div class="signature">
            {{ $user->name }}
        </div>
    </div>

    {{-- AI Generation Notice --}}
    <div class="ai-notice">
        This cover letter was automatically generated and customized for {{ $jobTitle }} at {{ $companyName }}.
        <br>Generated on {{ date('F j, Y') }} by StudAI Career Autonomous Agent
    </div>
</body>
</html>
