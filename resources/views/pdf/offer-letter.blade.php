<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offer Letter - {{ $offer->job_title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 40px;
        }
        
        .header {
            text-align: right;
            margin-bottom: 40px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
        }
        
        .date {
            margin-top: 10px;
            color: #666;
        }
        
        .greeting {
            margin-bottom: 20px;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .detail-table td {
            padding: 8px 0;
            vertical-align: top;
        }
        
        .detail-table td:first-child {
            font-weight: bold;
            width: 40%;
            color: #555;
        }
        
        .compensation-box {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .compensation-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .total-compensation {
            font-size: 16px;
            font-weight: bold;
            color: #4f46e5;
            border-top: 2px solid #4f46e5;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .benefits-list {
            list-style: none;
        }
        
        .benefits-list li {
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .benefits-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
        }
        
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            width: 250px;
            margin-top: 40px;
            padding-top: 5px;
        }
        
        .acceptance-section {
            margin-top: 60px;
            padding: 20px;
            border: 2px solid #4f46e5;
            border-radius: 8px;
            page-break-inside: avoid;
        }
        
        .acceptance-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #4f46e5;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #999;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        
        .expiry-notice {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 4px;
            padding: 10px;
            margin: 15px 0;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $offer->company->name ?? 'Company' }}</div>
        <div class="date">{{ now()->format('F j, Y') }}</div>
    </div>

    <div class="greeting">
        <p>Dear {{ $offer->candidate->name ?? 'Candidate' }},</p>
    </div>

    <div class="section">
        <p>We are pleased to extend this offer of employment for the position of <strong>{{ $offer->job_title }}</strong>. 
        We were impressed with your qualifications and believe you will be a valuable addition to our team.</p>
    </div>

    <div class="section">
        <div class="section-title">Position Details</div>
        <table class="detail-table">
            <tr>
                <td>Job Title:</td>
                <td>{{ $offer->job_title }}</td>
            </tr>
            @if($offer->department)
            <tr>
                <td>Department:</td>
                <td>{{ $offer->department }}</td>
            </tr>
            @endif
            <tr>
                <td>Employment Type:</td>
                <td>{{ ucfirst($offer->employment_type) }}</td>
            </tr>
            <tr>
                <td>Work Arrangement:</td>
                <td>{{ ucfirst($offer->work_arrangement) }}</td>
            </tr>
            @if($offer->work_location)
            <tr>
                <td>Location:</td>
                <td>{{ $offer->work_location }}</td>
            </tr>
            @endif
            @if($offer->reporting_to)
            <tr>
                <td>Reports To:</td>
                <td>{{ $offer->reporting_to }}</td>
            </tr>
            @endif
            <tr>
                <td>Start Date:</td>
                <td>{{ $offer->start_date?->format('F j, Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Compensation Package</div>
        <div class="compensation-box">
            <table class="detail-table">
                <tr>
                    <td>Base Salary:</td>
                    <td>{{ $offer->currency }} {{ number_format($offer->base_salary, 2) }} per {{ $offer->salary_period }}</td>
                </tr>
                @if($offer->signing_bonus)
                <tr>
                    <td>Signing Bonus:</td>
                    <td>{{ $offer->currency }} {{ number_format($offer->signing_bonus, 2) }}</td>
                </tr>
                @endif
                @if($offer->annual_bonus_target)
                <tr>
                    <td>Annual Bonus Target:</td>
                    <td>{{ $offer->annual_bonus_target }}% of base salary</td>
                </tr>
                @endif
            </table>
            <div class="total-compensation">
                <table class="detail-table">
                    <tr>
                        <td>Total First Year Compensation:</td>
                        <td>{{ $offer->currency }} {{ number_format($offer->total_compensation, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
        @if($offer->bonus_structure)
        <p style="margin-top: 10px;"><strong>Bonus Structure:</strong> {{ $offer->bonus_structure }}</p>
        @endif
    </div>

    @if($offer->equity_shares)
    <div class="section">
        <div class="section-title">Equity Compensation</div>
        <table class="detail-table">
            <tr>
                <td>Number of Shares:</td>
                <td>{{ number_format($offer->equity_shares) }}</td>
            </tr>
            @if($offer->equity_type)
            <tr>
                <td>Type:</td>
                <td>{{ $offer->equity_type }}</td>
            </tr>
            @endif
            @if($offer->vesting_schedule)
            <tr>
                <td>Vesting Schedule:</td>
                <td>{{ $offer->vesting_schedule }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    @if($offer->benefitsPackage)
    <div class="section">
        <div class="section-title">Benefits Package</div>
        <p><strong>{{ $offer->benefitsPackage->name }}</strong> (Estimated Annual Value: {{ $offer->currency }} {{ number_format($offer->benefitsPackage->total_value, 2) }})</p>
        
        @foreach($offer->benefitsPackage->getFormattedBenefits() as $category => $benefits)
        <div style="margin-top: 15px;">
            <p style="font-weight: bold; color: #555;">{{ $category }}</p>
            <ul class="benefits-list">
                @foreach($benefits as $benefit)
                <li>
                    {{ $benefit['name'] }}
                    @if(!empty($benefit['description']))
                        - {{ $benefit['description'] }}
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>
    @endif

    @if($offer->special_conditions)
    <div class="section">
        <div class="section-title">Special Conditions</div>
        <p>{{ $offer->special_conditions }}</p>
    </div>
    @endif

    <div class="expiry-notice">
        <strong>Important:</strong> This offer expires on <strong>{{ $offer->offer_expiry_date?->format('F j, Y') }}</strong>. 
        Please respond by this date to confirm your acceptance.
    </div>

    <div class="section">
        <p>This offer is contingent upon successful completion of a background check and verification of your eligibility to work.</p>
        <p style="margin-top: 15px;">We are excited about the possibility of you joining our team and look forward to your positive response.</p>
    </div>

    <div class="signature-section">
        <p>Sincerely,</p>
        <div class="signature-line">
            {{ $offer->creator->name ?? 'Hiring Manager' }}<br>
            {{ $offer->company->name ?? 'Company' }}
        </div>
    </div>

    <div class="acceptance-section">
        <div class="acceptance-title">ACCEPTANCE OF OFFER</div>
        <p>I, {{ $offer->candidate->name ?? '________________' }}, accept this offer of employment and agree to the terms and conditions outlined above.</p>
        
        <table style="width: 100%; margin-top: 30px;">
            <tr>
                <td style="width: 50%;">
                    <div class="signature-line">
                        Signature
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="signature-line">
                        Date
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>This is a confidential document. Offer ID: {{ $offer->uuid }}</p>
        <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
