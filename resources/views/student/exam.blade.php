@extends('layouts.app')

@section('content')
<style>
  @media print {
    body { background-color: white !important; color: black !important; }
    header, .print-hidden, button, nav, footer, .floating-support, #cbt-header { display: none !important; }
    #print-section-certificate, #printable-result-slip { display: block !important; visibility: visible !important; width: 100% !important; }
    #print-section-certificate { page-break-after: always !important; page-break-inside: avoid !important; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
  }
</style>

<div class="min-h-screen bg-slate-50 text-slate-800 pb-16">
  <!-- Sticky Header -->
  <header id="cbt-header" class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-xs print-hidden">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="bg-slate-900 text-white rounded-xl py-1 px-3.5 font-bold text-xs uppercase tracking-wider">ClassPortal CBT Engine</span>
        <div>
          <h1 class="text-base font-extrabold text-slate-900 line-clamp-1">{{ $exam->title }}</h1>
          <p class="text-xs font-semibold text-slate-500">{{ $exam->subject }} &bull; Prep Mode</p>
        </div>
      </div>
      <div class="flex items-center gap-2" id="header-controls">
        <button id="fullscreen-btn" onclick="toggleFullscreen()" class="p-2 text-slate-500 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition" title="Toggle Fullscreen Mode">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
        </button>
        <div id="timer-display" class="flex items-center gap-2 bg-rose-50 text-rose-700 px-4 py-2 rounded-2xl border border-rose-100 font-mono text-sm font-black">
          <svg class="w-4 h-4 text-rose-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span id="timer-text">00:00</span>
        </div>
      </div>
    </div>
  </header>

  <div class="max-w-5xl mx-auto px-4 mt-8">
    <!-- Exam Active State -->
    <div id="exam-active">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Question -->
        <div class="lg:col-span-2 space-y-4">
          <div class="p-4 sm:p-6 bg-white border border-slate-200 rounded-3xl shadow-xs space-y-4">
            <div class="flex items-center justify-between">
              <span id="q-counter" class="text-xs bg-slate-100 text-slate-700 py-1 px-3.5 rounded-full font-bold">Question 1 of {{ count($exam->questions) }}</span>
              <div class="flex items-center gap-2">
                <button id="flag-btn" onclick="toggleFlag()" class="p-2 rounded-xl border transition bg-white text-slate-400 border-slate-200 hover:text-slate-700 hover:bg-slate-50" title="Flag Question for Review">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                </button>
                <span id="q-marks" class="text-xs bg-indigo-50 text-indigo-700 py-1 px-3 rounded-full font-extrabold border border-indigo-100">+5 Mark</span>
              </div>
            </div>
            <h3 id="q-text" class="text-base sm:text-lg font-extrabold text-slate-800 leading-relaxed"></h3>
            <div id="options-container" class="space-y-2 sm:space-y-3"></div>
            <div class="flex items-center justify-between pt-4 border-t border-slate-150">
              <button id="prev-btn" onclick="goToQuestion(currentIndex - 1)" class="flex items-center gap-1.5 py-2 px-3 sm:py-2.5 sm:px-4 bg-slate-50 border border-slate-200 hover:bg-slate-100 disabled:opacity-40 rounded-xl font-bold text-xs text-slate-700 transition" disabled>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> Previous
              </button>
              <span id="q-nav-counter" class="text-xs text-slate-450 font-bold">Question 1 of {{ count($exam->questions) }}</span>
              <button id="next-btn" onclick="goToQuestion(currentIndex + 1)" class="flex items-center gap-1.5 py-2 px-3 sm:py-2.5 sm:px-4 bg-slate-50 border border-slate-200 hover:bg-slate-100 disabled:opacity-40 rounded-xl font-bold text-xs text-slate-700 transition">Next
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </button>
            </div>
          </div>
        </div>
        <!-- Right: Navigation Grid -->
        <div class="space-y-6">
          <div class="p-6 bg-white border border-slate-200 rounded-3xl shadow-xs space-y-4">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">CBT Navigation Center</h4>
            <div id="nav-grid" class="grid grid-cols-4 sm:grid-cols-5 gap-2"></div>
            <div class="border-t border-slate-100 pt-4 space-y-2 text-xs text-slate-500 leading-none">
              <div class="flex items-center gap-2"><span class="w-3 h-3 bg-indigo-600 rounded-md border border-indigo-700 block"></span><span>Current Active Q</span></div>
              <div class="flex items-center gap-2"><span class="w-3 h-3 bg-indigo-50 rounded-md border border-indigo-200 block"></span><span>Attempted Q</span></div>
              <div class="flex items-center gap-2"><span class="w-3 h-3 bg-slate-50 rounded-md border border-slate-200 block"></span><span>Unanswered Choice</span></div>
              <div class="flex items-center gap-2"><span class="w-3 h-3 bg-amber-400 rounded-md block"></span><span>Flagged for Review</span></div>
            </div>
            <button id="submit-btn" onclick="triggerSubmit()" class="w-full mt-4 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-extrabold text-xs uppercase tracking-widest rounded-xl transition-all shadow-md shadow-emerald-100 min-h-12 flex items-center justify-center cursor-pointer">Finish & Submit Exam</button>
          </div>
          <div class="p-5 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <div class="text-xs text-amber-800 space-y-1">
              <p class="font-bold">Security Lock Protocols Active</p>
              <p class="leading-relaxed">This exam is automatically saved to local caching systems. If you accidentally refresh, you will resume directly.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Results State -->
    <div id="exam-results" class="hidden space-y-8"></div>

    <!-- Printable Certificate -->
    <div id="print-section-certificate" class="hidden print:block p-10 bg-white min-h-[190mm]">
      <div class="border border-double border-amber-600 p-8 text-center bg-amber-50/20 max-w-5xl mx-auto rounded-3xl relative">
        <h1 class="text-3xl font-serif font-bold text-amber-900 uppercase">Certificate of Excellence</h1>
        <p class="italic text-sm text-slate-600 my-4">Presented to</p>
        <h2 id="cert-name" class="text-4xl font-serif font-black underline my-4 uppercase"></h2>
        <p id="cert-desc" class="text-sm text-slate-700 max-w-lg mx-auto"></p>
        <div id="cert-percentage" class="my-6 text-5xl font-black text-rose-700"></div>
        <div class="flex justify-between items-center text-xs text-slate-500 mt-12 px-12 pt-6 border-t border-dashed border-amber-300">
          <div class="text-left">Principal Assessor Name: <strong class="text-slate-800">Nwaigbo Augustine</strong><div class="h-[1px] bg-slate-350 w-32 mt-2"></div></div>
          <div id="cert-id" class="text-right">Official CBT Verification Code: <strong class="text-slate-800"></strong><div class="h-[1px] bg-slate-350 w-32 mt-2"></div></div>
        </div>
      </div>
    </div>

    <!-- Printable Result Slip -->
    <div id="printable-result-slip" class="hidden print:block p-10 bg-white font-sans text-xs min-h-[297mm]">
      <div class="space-y-6">
        <div class="text-center border-b-2 border-slate-900 pb-4">
          <h1 class="text-2xl font-black uppercase text-slate-900">REPUBLIC OF EDUCATION CLASS PORTAL</h1>
          <p class="text-xs uppercase font-extrabold text-slate-500 tracking-wider">Official Assessment Center &bull; Computer Based Testing Division</p>
          <p class="text-[10px] text-slate-400">Portal Link: brain-cbt.system</p>
        </div>
        <div class="text-center"><h2 class="text-sm bg-slate-900 text-white font-extrabold py-2 uppercase tracking-widest inline-block px-8 rounded-md">Candidate Result Slip</h2></div>
        <div id="slip-meta" class="grid grid-cols-2 gap-4 bg-slate-50 p-4 border border-slate-200 rounded-xl leading-relaxed"></div>
        <table class="w-full text-left border-collapse border border-slate-200">
          <thead><tr class="bg-slate-100 text-slate-700 text-[10px] uppercase font-black tracking-wider"><th class="p-3 border border-slate-200">Evaluation Factor</th><th class="p-3 border border-slate-200">Registered Metric</th><th class="p-3 border border-slate-200">Score Achieved</th><th class="p-3 border border-slate-200">Final Outcome</th></tr></thead>
          <tbody id="slip-table-body" class="font-semibold text-slate-800"></tbody>
        </table>
        <div id="slip-kpi" class="space-y-2"></div>
        <div class="grid grid-cols-2 pt-16 border-t border-slate-100 items-end">
          <div><p class="italic text-slate-500">Official Web Print Stamp</p><p class="text-[9px] text-slate-400 mt-2">Generated dynamically by brain-cbt system: <span id="print-date"></span></p></div>
          <div class="text-right"><p class="font-bold">Principal Assessment Center Signature</p><p class="font-serif italic text-lg text-indigo-600 my-1">Austin Nwaigbo</p><div class="h-[1px] bg-slate-350 w-48 ml-auto"></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const questions = @json($exam->questions);
const examId = '{{ $exam->id }}';
const examTitle = '{{ $exam->title }}';
const examSubject = '{{ $exam->subject }}';
const examLevel = '{{ $exam->level }}';
const examDuration = {{ $exam->duration }};
const studentId = '{{ $studentUser['id'] }}';
const studentName = '{{ $studentUser['name'] }}';

let currentIndex = 0;
let selectedAnswers = {};
let flaggedQuestions = {};
let secondsLeft = examDuration * 60;
let isExamActive = true;
let submitting = false;
let result = null;
let attempts = [];

function initExam() {
  // Resume saved progress
  const saved = localStorage.getItem('cbt_progress_' + examId);
  if (saved) {
    try {
      const p = JSON.parse(saved);
      if (p && p.secondsLeft > 0) {
        selectedAnswers = p.selectedAnswers || {};
        secondsLeft = p.secondsLeft;
        currentIndex = p.currentQuestionIndex || 0;
        flaggedQuestions = p.flaggedQuestions || {};
      }
    } catch(e) {}
  }
  // Load attempt history
  try {
    const hist = localStorage.getItem('brain_history_' + examId);
    if (hist) attempts = JSON.parse(hist);
  } catch(e) {}
  // Render
  buildNavGrid();
  renderQuestion();
  startTimer();
}

function buildNavGrid() {
  const grid = document.getElementById('nav-grid');
  grid.innerHTML = questions.map((_, i) => {
    const isAnswered = selectedAnswers[i] !== undefined;
    const isActive = currentIndex === i;
    const isFlagged = flaggedQuestions[i] === true;
    let cls = isActive ? 'bg-indigo-600 border-indigo-600 text-white shadow-xs' : isAnswered ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-600';
    return `<button onclick="goToQuestion(${i})" class="h-10 text-xs font-mono font-black relative rounded-lg border flex items-center justify-center transition-colors shadow-xs cursor-pointer ${cls}">${String(i + 1).padStart(2, '0')}${isFlagged ? '<span class="absolute top-0 right-0 w-2.5 h-2.5 bg-amber-500 rounded-bl-md border-t border-r border-white rounded-tr-md"></span>' : ''}</button>`;
  }).join('');
}

function renderQuestion() {
  const q = questions[currentIndex];
  if (!q) return;
  document.getElementById('q-text').innerHTML = q.question;
  document.getElementById('q-counter').textContent = 'Question ' + (currentIndex + 1) + ' of ' + questions.length;
  document.getElementById('q-nav-counter').textContent = 'Question ' + (currentIndex + 1) + ' of ' + questions.length;
  document.getElementById('q-marks').textContent = '+' + (q.marks || 5) + ' Mark';
  document.getElementById('prev-btn').disabled = currentIndex === 0;
  document.getElementById('next-btn').disabled = currentIndex === questions.length - 1;
  // Flag button
  const flagBtn = document.getElementById('flag-btn');
  if (flaggedQuestions[currentIndex]) {
    flagBtn.className = 'p-2 rounded-xl border transition bg-amber-50 text-amber-700 border-amber-300';
    flagBtn.innerHTML = '<svg class="w-3.5 h-3.5 fill-amber-500" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>';
  } else {
    flagBtn.className = 'p-2 rounded-xl border transition bg-white text-slate-400 border-slate-200 hover:text-slate-700 hover:bg-slate-50';
    flagBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>';
  }
  // Options
  const opts = document.getElementById('options-container');
  const selected = selectedAnswers[currentIndex];
  const optA = q.optionA || (q.options && q.options.A) || q.A || '';
  const optB = q.optionB || (q.options && q.options.B) || q.B || '';
  const optC = q.optionC || (q.options && q.options.C) || q.C || '';
  const optD = q.optionD || (q.options && q.options.D) || q.D || '';
  opts.innerHTML = [
    { key: 'A', label: optA },
    { key: 'B', label: optB },
    { key: 'C', label: optC },
    { key: 'D', label: optD },
  ].map(opt => {
    const isSelected = selected === opt.key;
    return `<button onclick="selectOption('${opt.key}')" class="w-full flex items-center justify-between text-left p-2.5 sm:p-3.5 rounded-xl border font-bold text-xs sm:text-sm transition duration-150 cursor-pointer ${isSelected ? 'bg-indigo-600/10 border-indigo-600 text-indigo-950 ring-2 ring-indigo-600/15' : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-700'}">
      <div class="flex items-center gap-2.5 sm:gap-3">
        <span class="w-7 h-7 rounded-lg font-mono font-black flex items-center justify-center shrink-0 border text-xs leading-none transition ${isSelected ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-500 border-slate-200'}">${opt.key}</span>
        <span>${opt.label}</span>
      </div>${isSelected ? '<div class="w-4 h-4 bg-indigo-600 text-white rounded-full flex items-center justify-center text-[10px]">\u2713</div>' : ''}
    </button>`;
  }).join('');
  buildNavGrid();
  autoSave();
}

function selectOption(key) {
  if (!isExamActive) return;
  selectedAnswers[currentIndex] = key;
  renderQuestion();
}

function toggleFlag() {
  if (!isExamActive) return;
  flaggedQuestions[currentIndex] = !flaggedQuestions[currentIndex];
  renderQuestion();
}

function goToQuestion(index) {
  if (index < 0 || index >= questions.length) return;
  currentIndex = index;
  renderQuestion();
}

function startTimer() {
  const timerEl = document.getElementById('timer-text');
  function tick() {
    if (!isExamActive || secondsLeft <= 0) {
      if (secondsLeft <= 0 && isExamActive) {
        isExamActive = false;
        triggerSubmit();
      }
      return;
    }
    secondsLeft--;
    const m = Math.floor(secondsLeft / 60);
    const s = secondsLeft % 60;
    timerEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    autoSave();
    setTimeout(tick, 1000);
  }
  tick();
}

function autoSave() {
  if (isExamActive && !result) {
    const state = { selectedAnswers, secondsLeft, currentQuestionIndex: currentIndex, flaggedQuestions };
    try { localStorage.setItem('cbt_progress_' + examId, JSON.stringify(state)); } catch(e) {}
  }
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(() => {});
  } else {
    document.exitFullscreen().catch(() => {});
  }
}

function triggerSubmit() {
  if (submitting || !isExamActive) return;
  if (!confirm('Are you sure you want to submit your exam? This action cannot be undone.')) return;
  submitting = true;
  isExamActive = false;
  document.getElementById('submit-btn').textContent = 'Scoring metrics...';
  document.getElementById('submit-btn').disabled = true;
  const timeSpent = (examDuration * 60) - secondsLeft;
  // Try API submission
  fetch('/api/exams/' + examId + '/submit', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ studentId, studentName, answers: selectedAnswers, timeSpent })
  }).then(r => r.json()).then(data => {
    if (data.success) {
      result = data.result;
      showResults();
      saveAttempt(data.result, timeSpent);
    } else {
      computeLocalResult(timeSpent);
    }
  }).catch(() => {
    computeLocalResult(timeSpent);
  });
}

function computeLocalResult(timeSpent) {
  let score = 0;
  let correct = 0;
  const failed = [];
  let totalMarks = 0;
  questions.forEach((q, i) => {
    const m = q.marks || 5;
    totalMarks += m;
    const chosen = selectedAnswers[i] || null;
    const isCorrect = chosen === q.correctAnswer;
    if (isCorrect) { score += m; correct++; }
    failed.push({
      question: q.question, optionA: q.optionA, optionB: q.optionB, optionC: q.optionC, optionD: q.optionD,
      selectedAnswer: chosen, correctAnswer: q.correctAnswer, isCorrect,
      marks: m, explanation: q.explanation || 'The correct answer is Option ' + q.correctAnswer + '.',
      topic: q.topic || 'General Topic'
    });
  });
  const percentage = totalMarks > 0 ? Math.round((score / totalMarks) * 100) : 0;
  result = {
    id: 'res_' + Math.random().toString(36).substring(2, 9),
    examId, examTitle, subject: examSubject,
    studentId, studentName, score, percentage,
    totalQuestions: questions.length, correctAnswers: correct,
    failedQuestions: failed, date: new Date().toISOString(),
    timeSpent, totalPossibleMarks: totalMarks,
  };
  showResults();
  saveAttempt(result, timeSpent);
}

function saveAttempt(resObj, timeSpent) {
  attempts.push({
    id: resObj.id, score: resObj.score, percentage: resObj.percentage,
    correctAnswers: resObj.correctAnswers, totalQuestions: resObj.totalQuestions,
    date: resObj.date, timeSpent
  });
  try {
    localStorage.setItem('brain_history_' + examId, JSON.stringify(attempts));
    localStorage.removeItem('cbt_progress_' + examId);
  } catch(e) {}
}

function showResults() {
  document.getElementById('exam-active').classList.add('hidden');
  document.getElementById('submit-btn').classList.add('hidden');
  const headerControls = document.getElementById('header-controls');
  headerControls.innerHTML = '<a href="{{ route("student.dashboard") }}" class="px-5 py-2 text-xs font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition-all">Return to Dashboard</a>';
  // Compute analytics
  const topicStats = {};
  questions.forEach((q, i) => {
    const tn = q.topic || 'General Concepts';
    if (!topicStats[tn]) topicStats[tn] = { correct: 0, total: 0 };
    topicStats[tn].total++;
    if (selectedAnswers[i] === q.correctAnswer) topicStats[tn].correct++;
  });
  let strongest = 'N/A', weakest = 'N/A', maxRate = -1, minRate = 101;
  Object.entries(topicStats).forEach(([name, st]) => {
    const r = (st.correct / st.total) * 100;
    if (r > maxRate) { maxRate = r; strongest = name; }
    if (r < minRate) { minRate = r; weakest = name; }
  });
  if (maxRate === -1) strongest = 'N/A';
  if (minRate === 101) weakest = 'N/A';
  const bestScore = attempts.length > 0 ? Math.max(...attempts.map(a => a.percentage)) : result.percentage;
  const avgScore = attempts.length > 0 ? Math.round(attempts.reduce((s, a) => s + a.percentage, 0) / attempts.length) : result.percentage;
  const qAttempted = Object.keys(selectedAnswers).length;
  const qSkipped = questions.length - qAttempted;
  const isPassed = result.percentage >= 50;

  // Certificate
  document.getElementById('cert-name').textContent = result.studentName;
  document.getElementById('cert-desc').innerHTML = 'For completing the computer-based evaluation CBT test score aggregates in <strong class="font-extrabold">' + examTitle + ' (' + examSubject + ')</strong> with a final score percentage of:';
  document.getElementById('cert-percentage').textContent = result.percentage + '%';
  document.getElementById('cert-id').innerHTML = 'Official CBT Verification Code: <strong class="text-slate-800">' + result.id + '</strong>';

  // Result Slip
  document.getElementById('slip-meta').innerHTML = '<div><span class="text-slate-450 uppercase font-bold text-[9px]">CANDIDATE STUDENT</span><p class="text-sm font-extrabold text-slate-900">' + result.studentName + '</p><p class="text-[10px] text-slate-500">Student ID: ' + result.studentId + '</p></div><div class="text-right"><span class="text-slate-450 uppercase font-bold text-[9px]">ASSESSMENT METRIC</span><p class="text-sm font-extrabold text-slate-900">' + examTitle + '</p><p class="text-[10px] text-slate-500">Subject Area: ' + examSubject + ' (' + examLevel + ')</p></div>';
  document.getElementById('slip-table-body').innerHTML = '<tr><td class="p-3 border border-slate-200">Aggregate Questions</td><td class="p-3 border border-slate-200">' + result.totalQuestions + ' Questions</td><td class="p-3 border border-slate-200">' + result.correctAnswers + ' Correct</td><td class="p-3 border border-slate-200 text-indigo-700">' + result.percentage + '%</td></tr><tr class="bg-slate-50"><td class="p-3 border border-slate-200">Session Elapsed</td><td class="p-3 border border-slate-200">' + examDuration + ' Minutes max</td><td class="p-3 border border-slate-200">' + formatTime(result.timeSpent || 0) + '</td><td class="p-3 border border-slate-200 text-emerald-600 font-extrabold uppercase">' + (isPassed ? 'Pass' : 'Fail') + '</td></tr>';
  document.getElementById('slip-kpi').innerHTML = '<h3 class="font-extrabold text-xs uppercase border-b border-slate-200 pb-1 text-slate-700">Detailed Subject Performance Chart</h3><div class="grid grid-cols-2 gap-4"><div><span class="text-[9px] text-slate-400 uppercase">Strongest Concept Block</span><strong class="block text-slate-800">' + strongest + '</strong></div><div><span class="text-[9px] text-slate-400 uppercase">Weakest Concept Area</span><strong class="block text-slate-850">' + weakest + '</strong></div></div>';
  document.getElementById('print-date').textContent = new Date().toLocaleString();

  // Attempt history
  const attemptsHtml = attempts.length === 0 ? '<p class="text-[11px] text-slate-400">First logged attempt completed.</p>' :
    '<div class="space-y-1.5 max-h-48 overflow-y-auto">' + attempts.map((a, i) =>
      '<div class="flex items-center justify-between p-2 bg-slate-50 rounded-lg text-xs leading-none border border-slate-100"><div><span class="font-extrabold text-slate-800">Attempt #' + (i + 1) + '</span><p class="text-[9px] text-slate-400 mt-0.5">' + new Date(a.date).toLocaleDateString() + '</p></div><span class="font-black uppercase py-1 px-2.5 rounded-md text-[10px] ' + (a.percentage >= 50 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600') + '">' + a.percentage + '%</span></div>'
    ).join('') + '</div>';

  const resultContainer = document.getElementById('exam-results');
  resultContainer.innerHTML = `
    <div class="p-8 bg-gradient-to-br from-indigo-700 via-indigo-800 to-indigo-900 rounded-3xl text-white text-left shadow-xl relative overflow-hidden print-hidden">
      <div class="absolute right-0 bottom-0 top-0 w-1/3 bg-[radial-gradient(#ffffff0a_2px,transparent_2px)]" style="background-size:16px 16px;pointer-events:none"></div>
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
          <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full text-xs font-bold text-indigo-200 border border-white/5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Official CBT Transcript
          </div>
          <h2 class="text-3xl font-black">${result.percentage >= 50 ? 'Outstanding Effort!' : 'Focus & Practise!'}</h2>
          <p class="text-indigo-200 text-xs max-w-lg font-medium leading-relaxed">Student name <strong class="text-white font-extrabold">${result.studentName}</strong> has logged submission metrics. Check subject breakdown, corrected answers, and download formal transcripts below.</p>
        </div>
        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/10 shrink-0 text-center min-w-[160px]">
          <span class="text-[10px] uppercase tracking-widest text-indigo-200 font-extrabold block mb-1">Percentage Grade</span>
          <p class="text-5xl font-black text-white">${result.percentage}%</p>
          <span class="text-[10px] mt-2 inline-block px-3 py-0.5 bg-white/20 rounded-full font-bold uppercase">${result.percentage >= 50 ? 'Passed' : 'Retake Required'}</span>
        </div>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8 pt-6 border-t border-white/10">
        <div class="bg-white/5 rounded-xl p-3"><span class="text-[10px] text-indigo-200 uppercase font-bold tracking-wider">Correct Score</span><p class="text-lg font-bold text-white">${result.score} Marks (${result.correctAnswers}/${result.totalQuestions})</p></div>
        <div class="bg-white/5 rounded-xl p-3"><span class="text-[10px] text-indigo-200 uppercase font-bold tracking-wider">Time Completed</span><p class="text-lg font-bold text-white">${formatTime(result.timeSpent || ((examDuration * 60) - secondsLeft))}</p></div>
        <div class="bg-white/5 rounded-xl p-3"><span class="text-[10px] text-indigo-200 uppercase font-bold tracking-wider">Attempt Timestamp</span><p class="text-xs font-bold text-white mt-1">${new Date(result.date).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</p></div>
        <div class="bg-white/5 rounded-xl p-3"><span class="text-[10px] text-indigo-200 uppercase font-bold tracking-wider">Evaluation Identity</span><p class="text-[10px] font-mono font-bold text-indigo-100 truncate mt-1.5">${result.id}</p></div>
      </div>
      <div class="flex flex-wrap items-center gap-3 mt-6 print-hidden">
        <button onclick="handlePrintCertificate()" class="px-5 py-3 bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2.5 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          Print Scholar Certificate
        </button>
        <button onclick="handlePrintResultSlip()" class="px-5 py-3 bg-slate-900 hover:bg-slate-850 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2.5 cursor-pointer">
          <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Download PDF Result Slip
        </button>
        <button onclick="handleRetake()" class="px-5 py-3 bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-extrabold text-xs rounded-xl transition-all flex items-center gap-2.5 cursor-pointer">
          <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          Retake Exam
        </button>
        <a href="{{ route("student.dashboard") }}" class="px-5 py-3 bg-slate-100 hover:bg-slate-250 text-slate-700 font-extrabold text-xs rounded-xl transition-all flex items-center gap-2.5 cursor-pointer">
          <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          Return to Dashboard
        </button>
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 print-hidden">
      <div class="p-6 bg-white border border-slate-150 rounded-2xl shadow-xs space-y-4 col-span-1">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-550 flex items-center gap-2">
          <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
          CBT History & Progression
        </h4>
        <div class="space-y-3.5">
          <div class="grid grid-cols-2 gap-2">
            <div class="p-3 bg-slate-50 rounded-xl text-center border border-slate-100"><span class="text-[9px] text-slate-400 uppercase font-black block">Best Score</span><strong class="text-lg font-black text-slate-900">${bestScore}%</strong></div>
            <div class="p-3 bg-slate-50 rounded-xl text-center border border-slate-100"><span class="text-[9px] text-slate-400 uppercase font-black block">Avg Score</span><strong class="text-lg font-black text-indigo-650">${avgScore}%</strong></div>
          </div>
          <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider pt-2 border-t border-slate-100">Previous Attempts (${attempts.length})</div>
          ${attemptsHtml}
        </div>
      </div>
      <div class="p-6 bg-white border border-slate-150 rounded-2xl shadow-xs space-y-4 col-span-2">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-550 flex items-center gap-2">
          <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
          CBT Subject Analytics Insights
        </h4>
        <div class="grid grid-cols-2 gap-4">
          <div class="p-4 bg-emerald-50/40 rounded-xl border border-emerald-100"><span class="text-[10px] text-emerald-700 uppercase font-black tracking-wider block">Strongest Area of Capability</span><strong class="text-sm font-black text-slate-900 mt-1 block">${strongest}</strong><p class="text-[10px] text-slate-400 mt-1 font-medium">Demonstrated top performance indices in this conceptual sector.</p></div>
          <div class="p-4 bg-rose-50/45 rounded-xl border border-rose-100"><span class="text-[10px] text-rose-500 uppercase font-black tracking-wider block">Target Weakest revision Area</span><strong class="text-sm font-black text-rose-900 mt-1 block">${weakest}</strong><p class="text-[10px] text-slate-400 mt-1 font-medium">Needs focused revision. Study the detailed explanation keys below.</p></div>
        </div>
        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-100 text-xs">
          <div><span class="text-slate-400 font-extrabold uppercase text-[9px] tracking-wider block mb-1">QUESTIONS ATTEMPTED</span><p class="text-base font-black text-slate-800">${qAttempted} of ${questions.length}</p></div>
          <div><span class="text-slate-400 font-extrabold uppercase text-[9px] tracking-wider block mb-1">UNANSWERED / SKIPPED</span><p class="text-base font-black text-amber-600">${qSkipped} skipping</p></div>
        </div>
      </div>
    </div>
    <div class="p-6 bg-white border border-slate-150 rounded-3xl shadow-sm space-y-4 print-hidden">
      <h4 class="text-xs font-extrabold uppercase tracking-widest text-slate-400 flex items-center gap-1">
        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        Sector / Topic Performance Distribution
      </h4>
      <div class="space-y-3.5">
        ${Object.entries(topicStats).map(([tn, st]) => {
          const rate = Math.round((st.correct / st.total) * 100);
          const barColor = rate >= 75 ? 'bg-emerald-500' : rate >= 50 ? 'bg-indigo-600' : 'bg-rose-500';
          return '<div class="space-y-1"><div class="flex items-center justify-between text-xs font-bold text-slate-700"><span>' + tn + '</span><span>' + st.correct + '/' + st.total + ' Correct (' + rate + '%)</span></div><div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden"><div class="h-full rounded-full transition-all duration-500 ' + barColor + '" style="width:' + rate + '%"></div></div></div>';
        }).join('')}
      </div>
    </div>
    <div class="p-6 bg-white border border-slate-150 rounded-3xl shadow-sm space-y-6 print-hidden">
      <div><h3 class="text-lg font-black text-slate-900 flex items-center gap-2"><svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Review Answers & Explanations Panel</h3><p class="text-xs text-slate-400 font-bold mt-1">Examine every question, correct choice matrix, your choice, and structural learning explanations.</p></div>
      <div class="space-y-6 divide-y divide-slate-100">
        ${result.failedQuestions.map((item, idx) => {
          const isCorrect = item.selectedAnswer === item.correctAnswer;
          const isNotAnswered = !item.selectedAnswer;
          let badgeColor = 'bg-rose-50 text-rose-700 border-rose-200';
          let badgeText = 'Wrong';
          if (isCorrect) { badgeColor = 'bg-emerald-50 text-emerald-700 border-emerald-200'; badgeText = 'Correct'; }
          else if (isNotAnswered) { badgeColor = 'bg-amber-50 text-amber-700 border-amber-200'; badgeText = 'Not Answered'; }
          const rOptA = item.optionA || (item.options && item.options.A) || item.A || '';
          const rOptB = item.optionB || (item.options && item.options.B) || item.B || '';
          const rOptC = item.optionC || (item.options && item.options.C) || item.C || '';
          const rOptD = item.optionD || (item.options && item.options.D) || item.D || '';
          const opts = [
            { key: 'A', label: rOptA }, { key: 'B', label: rOptB },
            { key: 'C', label: rOptC }, { key: 'D', label: rOptD }
          ];
          return '<div class="pt-6 space-y-3 text-left">' +
            '<div class="flex items-center justify-between"><span class="text-xs bg-slate-105 text-slate-650 py-1 px-2.5 rounded-full font-extrabold font-mono">Question ' + String(idx + 1).padStart(2, '0') + '</span><span class="text-[10px] uppercase font-black tracking-wide border py-1 px-3 rounded-full ' + badgeColor + '">' + badgeText + '</span></div>' +
            '<p class="text-sm sm:text-base font-bold text-slate-800 leading-relaxed">' + item.question + '</p>' +
            '<div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-4">' +
            opts.map(opt => {
              const isCorrectOpt = opt.key === item.correctAnswer;
              const isSelectedOpt = opt.key === item.selectedAnswer;
              let borderStyle = 'border-slate-150 hover:bg-slate-50';
              let markerColor = 'bg-slate-100 text-slate-500';
              let badge = '';
              if (isCorrectOpt) { borderStyle = 'bg-emerald-500/10 border-emerald-300 text-emerald-950'; markerColor = 'bg-emerald-500 text-white'; badge = '<span class="text-[10px] font-extrabold text-emerald-600 ml-auto select-none font-mono uppercase">Correct Choice</span>'; }
              else if (isSelectedOpt && !isCorrectOpt) { borderStyle = 'bg-rose-500/10 border-rose-300 text-rose-950'; markerColor = 'bg-rose-500 text-white'; badge = '<span class="text-[10px] font-extrabold text-rose-600 ml-auto select-none font-mono uppercase">Your Choice</span>'; }
              return '<div class="p-3.5 border rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-3 transition ' + borderStyle + '"><span class="w-6 h-6 rounded-md flex items-center justify-center font-mono font-bold shrink-0 text-xs shadow-xs ' + markerColor + '">' + opt.key + '</span><span>' + opt.label + '</span>' + badge + '</div>';
            }).join('') + '</div>' +
            '<div class="p-4 bg-slate-50 border border-slate-150 rounded-2xl flex items-start gap-2.5 mt-2"><svg class="w-4 h-4 text-indigo-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><div class="text-xs text-slate-600 leading-relaxed font-semibold"><strong class="text-slate-900 font-extrabold">Assessor Explanation Note:</strong> <span>' + (item.explanation || 'The correct answer is Option ' + item.correctAnswer + '.') + '</span></div></div>' +
            '</div>';
        }).join('')}
      </div>
    </div>
  `;
  resultContainer.classList.remove('hidden');
}

function formatTime(s) {
  if (s < 60) return s + 's';
  const m = Math.floor(s / 60);
  const sec = s % 60;
  return m + 'm ' + sec + 's';
}

function handlePrintCertificate() {
  alert('To Save PDF Certificate:\n1. Change Printer/Destination target to \'Save as PDF\'.\n2. Set Layout to \'Landscape\' to fit the diploma grid cleanly.\n3. Make sure background graphics are turned ON under more settings.');
  window.print();
}

function handlePrintResultSlip() {
  alert('To Print standard A4 Results Report:\n1. Change Printer/Destination target to \'Save as PDF\'.\n2. Set Layout to \'Portrait\'.\n3. Click printed sheet to save.');
  window.print();
}

function handleRetake() {
  if (confirm('Are you sure you want to retake this exam? This will reset your current timer and answers slate.')) {
    selectedAnswers = {};
    flaggedQuestions = {};
    secondsLeft = examDuration * 60;
    isExamActive = true;
    result = null;
    currentIndex = 0;
    submitting = false;
    document.getElementById('exam-results').classList.add('hidden');
    document.getElementById('exam-results').innerHTML = '';
    document.getElementById('exam-active').classList.remove('hidden');
    document.getElementById('submit-btn').classList.remove('hidden');
    document.getElementById('submit-btn').disabled = false;
    document.getElementById('submit-btn').textContent = 'Finish & Submit Exam';
    document.getElementById('header-controls').innerHTML = `
      <button onclick="toggleFullscreen()" class="p-2 text-slate-500 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition" title="Toggle Fullscreen Mode">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
      </button>
      <div class="flex items-center gap-2 bg-rose-50 text-rose-700 px-4 py-2 rounded-2xl border border-rose-100 font-mono text-sm font-black">
        <svg class="w-4 h-4 text-rose-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span id="timer-text">${formatTimeClock(secondsLeft)}</span>
      </div>`;
    initExam();
  }
}

function formatTimeClock(total) {
  const m = Math.floor(total / 60);
  const s = total % 60;
  return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
}

document.addEventListener('DOMContentLoaded', initExam);
window.addEventListener('beforeunload', function(e) {
  if (isExamActive && !result) {
    e.preventDefault();
    e.returnValue = 'Warning: Leaving or refreshing this page will abort your ongoing CBT session. Your progress will be saved where you left off.';
  }
});
</script>
@endsection