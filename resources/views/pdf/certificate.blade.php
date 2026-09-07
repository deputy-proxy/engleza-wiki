<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>engleza.wiki Certificate</title>
  <style>
    @page { margin: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; margin: 0; padding: 0; color: #0f1e5c; }
    .frame { padding: 40px; border: 8px solid #2c7cff; margin: 24px; height: calc(100% - 48px); position: relative; }
    .inner { border: 1px solid #cbdcf9; padding: 40px 50px; text-align: center; }
    h1 { font-size: 22px; letter-spacing: 6px; color: #2c7cff; margin: 0 0 8px; text-transform: uppercase; font-weight: bold; }
    h2 { font-size: 44px; margin: 30px 0 10px; color: #0f1e5c; }
    .subtitle { font-size: 14px; color: #223cd1; margin-bottom: 30px; text-transform: uppercase; letter-spacing: 3px; }
    .name { font-size: 40px; font-weight: bold; color: #0f1e5c; margin: 15px 0; border-bottom: 2px solid #2c7cff; padding-bottom: 10px; display: inline-block; }
    .level-box { display: inline-block; background: #2c7cff; color: white; font-size: 72px; font-weight: bold; padding: 20px 60px; border-radius: 20px; margin: 30px 0; }
    .stats { margin-top: 30px; }
    .stat { display: inline-block; margin: 0 25px; }
    .stat-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 2px; }
    .stat-value { font-size: 22px; font-weight: bold; color: #0f1e5c; }
    .footer { margin-top: 30px; font-size: 11px; color: #64748b; }
    .brand { position: absolute; top: 20px; left: 30px; font-size: 16px; font-weight: bold; color: #2c7cff; }
    .cert-id { position: absolute; bottom: 20px; right: 30px; font-size: 10px; color: #94a3b8; font-family: monospace; }
  </style>
</head>
<body>
  <div class="frame">
    <div class="brand">engleza.wiki</div>
    <div class="inner">
      <h1>Certificate of Achievement</h1>
      <div class="subtitle">Free English Level Test</div>

      <div style="margin-top: 30px;">This certificate is proudly presented to</div>
      <div class="name">{{ $name }}</div>

      <div style="margin-top: 24px;">for successfully completing</div>
      <div style="font-size: 22px; font-weight: bold; margin: 10px 0;">{{ $test->title }}</div>

      <div style="margin-top: 30px; font-size: 15px;">Assessed CEFR level</div>
      <div class="level-box">{{ $result?->level ?? $attempt->level_result ?? '—' }}</div>

      <div class="stats">
        <div class="stat"><div class="stat-label">Overall Score</div><div class="stat-value">{{ $result?->overall_percentage ?? $attempt->percentage }}%</div></div>
        @if($result?->grammar_score !== null)
          <div class="stat"><div class="stat-label">Grammar</div><div class="stat-value">{{ $result->grammar_score }}%</div></div>
        @endif
        @if($result?->vocabulary_score !== null)
          <div class="stat"><div class="stat-label">Vocabulary</div><div class="stat-value">{{ $result->vocabulary_score }}%</div></div>
        @endif
        @if($result?->reading_score !== null)
          <div class="stat"><div class="stat-label">Reading</div><div class="stat-value">{{ $result->reading_score }}%</div></div>
        @endif
      </div>

      <div class="footer">
        Issued on {{ $issued }} · engleza.wiki — Free English resources for learners and teachers
      </div>
    </div>
    <div class="cert-id">{{ $certificate_id }}</div>
  </div>
</body>
</html>
