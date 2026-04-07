<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 14px; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
  h2   { color: #3D1F3D; }
  h3   { color: #3D1F3D; margin-top: 24px; font-size: 15px; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  th, td { padding: 10px 14px; border: 1px solid #e0e0e0; text-align: left; }
  th { background: #f5f0f5; font-weight: 700; color: #3D1F3D; }
  .highlight { background: #fff8e1; }
  .footer { margin-top: 24px; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 12px; }
</style>
</head>
<body>

<h2>Good morning, {{ $agent->name }}! 👋</h2>

@if(count($birthdays) > 0)
<h3>🎂 Birthdays today</h3>
<table>
  <thead><tr><th>Student</th></tr></thead>
  <tbody>
    @foreach($birthdays as $s)
    <tr class="highlight"><td>{{ $s->name }}</td></tr>
    @endforeach
  </tbody>
</table>
@endif

@if(count($examsToday) > 0)
<h3>📝 Exams today — send good luck!</h3>
<table>
  <thead><tr><th>Student</th></tr></thead>
  <tbody>
    @foreach($examsToday as $s)
    <tr class="highlight"><td>{{ $s->name }}</td></tr>
    @endforeach
  </tbody>
</table>
@endif

@if($pendingMessages > 0)
<h3>📨 Scheduled messages due today</h3>
<p>You have <strong>{{ $pendingMessages }}</strong> scheduled message(s) to send today. Open the WhatsApp extension to send them.</p>
@endif

<h3>📊 Student status summary</h3>
@php
$statusLabels = [
    'waiting_initial_documents' => 'Waiting for Documents (Initial)',
    'first_contact_made'        => 'First Contact Made',
    'waiting_offer_letter'      => 'Waiting for Offer Letter',
    'waiting_english_exam'      => 'Waiting for English Exam',
    'waiting_duolingo'          => 'Waiting for Duolingo',
    'waiting_reapplication'     => 'Waiting for Reapplication',
    'waiting_college_documents' => 'Waiting for Documents (College)',
    'waiting_college_response'  => 'Waiting for College Response',
    'waiting_final_letter'      => 'Waiting for Final Letter',
    'waiting_payment'           => 'Waiting for Payment',
    'waiting_student_response'  => 'Waiting for Student Response',
    'cancelled'                 => 'Cancelled',
    'concluded'                 => 'Concluded',
];
@endphp
<table>
  <thead>
    <tr><th>Status</th><th>Students</th></tr>
  </thead>
  <tbody>
    @foreach($summary as $status => $row)
    @if($row['count'] > 0)
    <tr>
      <td>{{ $statusLabels[$status] ?? $status }}</td>
      <td>{{ $row['count'] }}</td>
    </tr>
    @endif
    @endforeach
  </tbody>
</table>

<p style="margin-top:16px">Log in to the platform or open your WhatsApp extension to get started.</p>

<div class="footer">CI Ireland CS Platform · Automated daily digest</div>

</body>
</html>
