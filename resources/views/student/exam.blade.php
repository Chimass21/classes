@extends('layouts.app')

@section('content')
<style>
  @media print {
    body { background-color: white !important; color: black !important; }
    header, .print-hidden, button, nav, footer, .floating-support, #cbt-header { display: none !important; }
    #print-section-certificate, #printable-result-slip { display: block !important; visibility: visible !important; width: 100% !important; }
    #print-section-certificate { page-break-inside: avoid !important; page-break-before: always !important; }
    #printable-graded-script { display: none !important; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
  }
  .nav-btn { transition: all 0.15s ease; }
  .nav-btn:hover:not(:disabled) { transform: scale(1.05); }
  .nav-btn:active:not(:disabled) { transform: scale(0.95); }
  .opt-btn { transition: all 0.15s ease; }
  .opt-btn:hover { transform: translateY(-1px); }
  .opt-btn:active { transform: translateY(0); }
  .fade-in { animation: fadeIn 0.3s ease; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  .slide-up { animation: slideUp 0.4s ease; }
  @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
  .bar-animate { animation: barGrow 0.8s ease; }
  @keyframes barGrow { from { width: 0; } }
  .result-card { animation: cardFade 0.5s ease both; }
  .result-card:nth-child(1) { animation-delay: 0.05s; }
  .result-card:nth-child(2) { animation-delay: 0.1s; }
  .result-card:nth-child(3) { animation-delay: 0.15s; }
  .result-card:nth-child(4) { animation-delay: 0.2s; }
  .result-card:nth-child(5) { animation-delay: 0.25s; }
  @keyframes cardFade { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="min-h-screen bg-slate-50 text-slate-800 pb-16">
  <!-- Sticky Header -->
  <header id="cbt-header" class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-xs print-hidden">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 py-2 sm:py-3 flex items-center justify-between gap-2">
      <div class="flex items-center gap-2 sm:gap-3 min-w-0">
        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl py-1 px-2.5 sm:px-3.5 font-bold text-[10px] sm:text-xs uppercase tracking-wider shrink-0">CBT Engine</span>
        <div class="min-w-0">
          <h1 class="text-sm sm:text-base font-extrabold text-slate-900 truncate">{{ $exam->title }}</h1>
          <p class="text-[10px] sm:text-xs font-semibold text-slate-500 truncate">{{ $exam->subject }} &bull; Prep Mode</p>
        </div>
      </div>
      <div class="flex items-center gap-1.5 sm:gap-2 shrink-0" id="header-controls">
        <button id="fullscreen-btn" onclick="toggleFullscreen()" class="p-2 text-slate-700 hover:text-slate-900 bg-slate-200 hover:bg-slate-300 rounded-xl transition" title="Toggle Fullscreen Mode">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
        </button>
        <div id="timer-display" class="flex items-center gap-1.5 sm:gap-2 bg-rose-50 text-rose-700 px-2.5 sm:px-4 py-1.5 sm:py-2 rounded-xl sm:rounded-2xl border border-rose-100 font-mono text-xs sm:text-sm font-black">
          <svg class="w-3 h-3 sm:w-4 sm:h-4 text-rose-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span id="timer-text">00:00</span>
        </div>
      </div>
    </div>
  </header>

  <div class="max-w-7xl mx-auto px-3 sm:px-4 mt-4 sm:mt-8">
    <!-- Exam Active State -->
    <div id="exam-active">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Main: Question Area -->
        <div class="lg:col-span-3 space-y-4">
          <div class="p-4 sm:p-6 bg-white border border-slate-200 rounded-2xl sm:rounded-3xl shadow-xs space-y-5">
            <div class="flex items-center justify-between flex-wrap gap-2">
              <span id="q-counter" class="text-xs bg-indigo-50 text-indigo-700 py-1.5 px-3.5 rounded-full font-bold border border-indigo-100">Question 1 of {{ count($exam->questions) }}</span>
              <div class="flex items-center gap-2">
                <button id="flag-btn" onclick="toggleFlag()" class="p-2 rounded-xl border transition bg-white text-slate-600 border-slate-300 hover:text-slate-800 hover:bg-slate-50" title="Flag Question for Review">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                </button>
                <span id="q-marks" class="text-xs bg-emerald-50 text-emerald-700 py-1 px-3 rounded-full font-extrabold border border-emerald-100">+5 Mark</span>
              </div>
            </div>
            <div class="min-h-[120px]">
              <h3 id="q-text" class="text-base sm:text-lg font-extrabold text-slate-800 leading-relaxed"></h3>
            </div>
            <div id="options-container" class="space-y-2.5 sm:space-y-3"></div>
            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
              <button id="prev-btn" onclick="goToQuestion(currentIndex - 1)" class="flex items-center gap-1.5 py-2.5 px-3.5 sm:py-2.5 sm:px-4 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl font-bold text-xs text-slate-700 transition-all shadow-xs" disabled>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> Previous
              </button>
              <span id="q-nav-counter" class="text-xs text-slate-400 font-bold hidden sm:block">Question 1 of {{ count($exam->questions) }}</span>
              <button id="next-btn" onclick="goToQuestion(currentIndex + 1)" class="flex items-center gap-1.5 py-2.5 px-3.5 sm:py-2.5 sm:px-4 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl font-bold text-xs text-slate-700 transition-all shadow-xs">Next
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </button>
            </div>
          </div>
          <!-- Question Numbers Bar -->
          <div class="p-3 sm:p-4 bg-white border border-slate-200 rounded-2xl sm:rounded-2xl shadow-xs">
            <div class="flex items-center justify-between mb-2">
              <h4 class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Questions</h4>
              <span id="answered-count" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">0/{{ count($exam->questions) }}</span>
            </div>
            <div id="nav-grid" class="flex flex-wrap gap-1"></div>
            <div class="mt-3 pt-2 border-t border-slate-100 flex flex-wrap gap-x-3 gap-y-1 text-[9px] text-slate-500 font-semibold leading-tight">
              <div class="flex items-center gap-1"><span class="w-2 h-2 bg-indigo-600 rounded border border-indigo-700 shrink-0"></span><span>Current</span></div>
              <div class="flex items-center gap-1"><span class="w-2 h-2 bg-emerald-400 rounded shrink-0"></span><span>Answered</span></div>
              <div class="flex items-center gap-1"><span class="w-2 h-2 bg-slate-100 rounded border border-slate-300 shrink-0"></span><span>Unanswered</span></div>
              <div class="flex items-center gap-1"><span class="w-2 h-2 bg-amber-400 rounded shrink-0"></span><span>Flagged</span></div>
            </div>
          </div>

          <!-- Submit Card -->
          <div class="p-4 sm:p-5 bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl shadow-lg shadow-emerald-200/40">
            <button id="submit-btn" onclick="triggerSubmit()" class="w-full py-3.5 bg-white hover:bg-emerald-50 text-emerald-700 font-extrabold text-xs uppercase tracking-widest rounded-xl transition-all shadow-sm cursor-pointer flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              Finish & Submit
            </button>
            <p class="text-[10px] text-emerald-200 text-center mt-2 font-semibold">Your progress is auto-saved locally</p>
          </div>

          <!-- Security Info -->
          <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3">
            <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <div class="text-[11px] text-amber-800 space-y-1">
              <p class="font-bold">Auto-Saved</p>
              <p class="leading-relaxed">Your answers are saved locally. If you refresh, you'll resume where you left off.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Results State -->
    <div id="exam-results" class="hidden space-y-6"></div>

    <!-- Printable Graded Script (first in print order) -->
    <div id="printable-graded-script" class="hidden print:block p-6 sm:p-10 bg-white font-sans min-h-[297mm]">
      <div id="graded-script-content" class="max-w-5xl mx-auto space-y-6"></div>
    </div>

    <!-- Printable Result Slip -->
    <div id="printable-result-slip" class="hidden print:block p-6 sm:p-10 bg-white font-sans text-[9px] sm:text-xs min-h-[297mm]">
      <div class="space-y-6 max-w-4xl mx-auto">
        <div class="text-center border-b-2 border-slate-900 pb-4">
          <h1 class="text-xl sm:text-2xl font-black uppercase text-slate-900 tracking-tight">Republic of Education Class Portal</h1>
          <p class="text-xs uppercase font-extrabold text-slate-500 tracking-wider mt-1">Official Assessment Center &bull; Computer Based Testing Division</p>
        </div>
        <div class="text-center">
          <h2 class="text-sm bg-slate-900 text-white font-extrabold py-2 uppercase tracking-widest inline-block px-8 rounded-md">Candidate Result Slip</h2>
        </div>
        <div id="slip-meta" class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 sm:p-5 border border-slate-200 rounded-xl leading-relaxed"></div>
        <table class="w-full text-left border-collapse border border-slate-300">
          <thead><tr class="bg-slate-100 text-slate-700 text-[10px] uppercase font-black tracking-wider"><th class="p-3 border border-slate-300">Evaluation Factor</th><th class="p-3 border border-slate-300">Registered Metric</th><th class="p-3 border border-slate-300">Score Achieved</th><th class="p-3 border border-slate-300">Final Outcome</th></tr></thead>
          <tbody id="slip-table-body" class="font-semibold text-slate-800"></tbody>
        </table>
        <div id="slip-kpi" class="space-y-3"></div>
        <div class="flex flex-col sm:flex-row justify-between items-end pt-8 border-t border-slate-200 gap-4">
          <div><p class="italic text-slate-500 text-xs">Official Web Print Stamp</p><p class="text-[9px] text-slate-400 mt-1">Generated: <span id="print-date"></span></p></div>
          <div class="text-right"><p class="font-bold text-xs">Principal's Signature</p><p class="font-serif italic text-base text-indigo-600 my-1">Austin Nwaigbo</p><div class="h-px bg-slate-300 w-48 ml-auto"></div></div>
        </div>
      </div>
    </div>

    <!-- Printable Certificate (last in print order) -->
    <div id="print-section-certificate" class="hidden print:block p-4 sm:p-10 bg-white min-h-[190mm]">
      <div class="border-4 border-double border-amber-600 p-6 sm:p-10 text-center bg-gradient-to-b from-amber-50/40 to-white max-w-5xl mx-auto rounded-2xl sm:rounded-3xl relative shadow-xl">
        <div class="absolute top-4 left-4 w-16 h-16 border-t-4 border-l-4 border-amber-600 rounded-tl-xl"></div>
        <div class="absolute top-4 right-4 w-16 h-16 border-t-4 border-r-4 border-amber-600 rounded-tr-xl"></div>
        <div class="absolute bottom-4 left-4 w-16 h-16 border-b-4 border-l-4 border-amber-600 rounded-bl-xl"></div>
        <div class="absolute bottom-4 right-4 w-16 h-16 border-b-4 border-r-4 border-amber-600 rounded-br-xl"></div>
        <div class="relative z-10">
          <h1 class="text-3xl sm:text-4xl font-serif font-bold text-amber-900 uppercase tracking-wide">Certificate of Excellence</h1>
          <div class="w-24 h-1 bg-amber-500 mx-auto my-4 rounded-full"></div>
          <p class="italic text-sm text-slate-600">Presented to</p>
          <h2 id="cert-name" class="text-3xl sm:text-4xl font-serif font-black underline underline-offset-8 my-4 uppercase text-slate-900"></h2>
          <p id="cert-desc" class="text-sm text-slate-700 max-w-lg mx-auto leading-relaxed"></p>
          <div id="cert-percentage" class="my-6 text-5xl sm:text-6xl font-black text-amber-700"></div>
          <div class="flex flex-col sm:flex-row justify-between items-center text-xs text-slate-500 mt-10 sm:mt-16 pt-6 border-t border-dashed border-amber-300 gap-4">
            <div class="text-left">Principal Assessor: <strong class="text-slate-800 block mt-1">Nwaigbo Augustine</strong></div>
            <div id="cert-id" class="text-right">Verification Code: <strong class="text-slate-800 block mt-1 font-mono"></strong></div>
          </div>
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
const examDefaultMarks = {{ $exam->defaultMarks ?? 5 }};
const studentId = '{{ $studentUser['id'] }}';
const studentName = '{{ $studentUser['name'] }}';

let currentIndex = 0;
let selectedAnswers = {};
let flaggedQuestions = {};
let secondsLeft = examDuration * 60;
let isExamActive = true;
let submitting = false;
let submitted = false;
let result = null;
let attempts = [];
let autoSaveInterval = null;
let timerInterval = null;
let endTime = null; // Absolute timestamp when exam should end

function initExam() {
  // Check if already submitted (persistent flag)
  if (localStorage.getItem('cbt_submitted_' + examId) === 'true' || sessionStorage.getItem('cbt_submitted_' + examId) === 'true') {
    showAlreadySubmitted();
    return;
  }

  // Check for sticky redirect after submit
  const postSubmit = sessionStorage.getItem('cbt_postsubmit_' + examId);
  if (postSubmit) {
    sessionStorage.removeItem('cbt_postsubmit_' + examId);
    try {
      const savedResult = JSON.parse(postSubmit);
      result = savedResult;
      submitted = true;
      isExamActive = false;
      submitting = true;
      showResults();
      saveAttempt(savedResult, examDuration * 60);
      return;
    } catch(e) {}
  }

  const saved = localStorage.getItem('cbt_progress_' + examId);
  if (saved) {
    try {
      const p = JSON.parse(saved);
      if (p && !p.submitted) {
        selectedAnswers = p.selectedAnswers || {};
        secondsLeft = Math.max(0, p.secondsLeft || 0);
        endTime = p.endTime || null;
        currentIndex = p.currentQuestionIndex || 0;
        flaggedQuestions = p.flaggedQuestions || {};
      }
    } catch(e) {}
  }

  // If endTime is not set or invalid, calculate from now
  if (!endTime) {
    endTime = Date.now() + secondsLeft * 1000;
  } else {
    // Recalculate secondsLeft from endTime
    const remaining = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
    if (remaining > 0) {
      secondsLeft = remaining;
    } else {
      secondsLeft = 0;
      // Time already expired on page load — auto-submit immediately
      setTimeout(() => triggerSubmit(true), 100);
      return;
    }
  }

  // Attempt to restore answers from server auto-save
  restoreServerAutoSave();

  try {
    const hist = localStorage.getItem('brain_history_' + examId);
    if (hist) attempts = JSON.parse(hist);
  } catch(e) {}
  buildNavGrid();
  updateAnsweredCount();
  renderQuestion();
  startTimer();
  startAutoSaveInterval();
  initTimerSync();
}

function buildNavGrid() {
  const grid = document.getElementById('nav-grid');
  grid.innerHTML = questions.map((_, i) => {
    const isAnswered = selectedAnswers[i] !== undefined;
    const isActive = currentIndex === i;
    const isFlagged = flaggedQuestions[i] === true;
    let cls = 'nav-btn w-7 h-7 text-[10px] font-extrabold rounded border flex items-center justify-center transition-all cursor-pointer ';
    if (isActive) {
      cls += 'bg-indigo-600 border-indigo-600 text-white scale-110 shadow-sm';
    } else if (isAnswered) {
      cls += 'bg-emerald-50 border-emerald-300 text-emerald-700 hover:bg-emerald-100';
    } else if (isFlagged) {
      cls += 'bg-amber-50 border-amber-300 text-amber-700 hover:bg-amber-100';
    } else {
      cls += 'bg-white border-slate-200 text-slate-600 hover:bg-slate-100 hover:border-slate-300';
    }
    return `<button onclick="goToQuestion(${i})" class="${cls}">${i + 1}</button>`;
  }).join('');
  grid.querySelectorAll('button').forEach((btn, i) => {
    if (currentIndex === i) btn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  });
}

function updateAnsweredCount() {
  const count = Object.keys(selectedAnswers).length;
  document.getElementById('answered-count').textContent = count + '/' + questions.length;
}

function renderQuestion() {
  const q = questions[currentIndex];
  if (!q) return;
  document.getElementById('q-text').textContent = q.question;
  document.getElementById('q-counter').textContent = 'Question ' + (currentIndex + 1) + ' of ' + questions.length;
  document.getElementById('q-nav-counter').textContent = 'Question ' + (currentIndex + 1) + ' of ' + questions.length;
  document.getElementById('q-marks').textContent = '+' + (q.marks || examDefaultMarks) + ' Mark';
  document.getElementById('prev-btn').disabled = currentIndex === 0;
  document.getElementById('next-btn').disabled = currentIndex === questions.length - 1;
  const flagBtn = document.getElementById('flag-btn');
  if (flaggedQuestions[currentIndex]) {
    flagBtn.className = 'p-2 rounded-xl border transition bg-amber-50 text-amber-700 border-amber-300 hover:bg-amber-100';
    flagBtn.innerHTML = '<svg class="w-3.5 h-3.5 fill-amber-500" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>';
  } else {
    flagBtn.className = 'p-2 rounded-xl border transition bg-white text-slate-600 border-slate-300 hover:text-slate-800 hover:bg-slate-50';
    flagBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>';
  }
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
    return `<button onclick="selectOption('${opt.key}')" class="opt-btn w-full flex items-center justify-between text-left p-3 sm:p-3.5 rounded-xl border font-bold text-xs sm:text-sm transition-all duration-150 cursor-pointer ${isSelected ? 'bg-indigo-50 border-indigo-400 text-indigo-900 ring-2 ring-indigo-200 shadow-sm' : 'bg-white hover:bg-slate-50 border-slate-200 hover:border-slate-300 text-slate-700 shadow-xs'}">
      <div class="flex items-center gap-3 min-w-0 flex-1">
        <span class="w-8 h-8 rounded-lg font-mono font-black flex items-center justify-center shrink-0 border text-sm leading-none transition ${isSelected ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-slate-200 text-slate-700 border-slate-300'}">${opt.key}</span>
        <span class="break-words min-w-0 leading-snug">${opt.label}</span>
      </div>${isSelected ? '<div class="w-5 h-5 bg-indigo-600 text-white rounded-full flex items-center justify-center text-[10px] shrink-0 shadow-sm">&#10003;</div>' : ''}
    </button>`;
  }).join('');
  buildNavGrid();
  updateAnsweredCount();
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
  if (timerInterval) clearInterval(timerInterval);
  const timerEl = document.getElementById('timer-text');
  timerInterval = setInterval(function() {
    if (!isExamActive || submitted || submitting) {
      return;
    }
    // Calculate remaining time from absolute endTime
    if (endTime) {
      secondsLeft = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
    }
    if (secondsLeft <= 0) {
      secondsLeft = 0;
      timerEl.textContent = '00:00';
      timerEl.parentElement.className = 'flex items-center gap-1.5 sm:gap-2 bg-red-600 text-white px-2.5 sm:px-4 py-1.5 sm:py-2 rounded-xl sm:rounded-2xl border border-red-700 font-mono text-xs sm:text-sm font-black animate-pulse';
      isExamActive = false;
      clearInterval(timerInterval);
      clearInterval(autoSaveInterval);
      triggerSubmit(true);
      return;
    }
    const m = Math.floor(secondsLeft / 60);
    const s = secondsLeft % 60;
    timerEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    // Low time warning
    if (secondsLeft <= 60) {
      timerEl.parentElement.className = 'flex items-center gap-1.5 sm:gap-2 bg-red-50 text-red-700 px-2.5 sm:px-4 py-1.5 sm:py-2 rounded-xl sm:rounded-2xl border border-red-100 font-mono text-xs sm:text-sm font-black';
    }
    autoSave();
  }, 1000);
}

function initTimerSync() {
  // When the browser tab becomes visible again, recalculate time immediately
  document.addEventListener('visibilitychange', function() {
    if (!document.hidden && isExamActive && !submitted) {
      if (endTime) {
        secondsLeft = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
      }
      if (secondsLeft <= 0) {
        triggerSubmit(true);
      }
    }
  });
}

function autoSave() {
  if (isExamActive && !result && !submitted) {
    const state = {
      selectedAnswers, secondsLeft,
      currentQuestionIndex: currentIndex,
      flaggedQuestions,
      endTime,
      submitted: false
    };
    try { localStorage.setItem('cbt_progress_' + examId, JSON.stringify(state)); } catch(e) {}
  }
}

function startAutoSaveInterval() {
  if (autoSaveInterval) clearInterval(autoSaveInterval);
  autoSaveInterval = setInterval(function() {
    if (isExamActive && !submitted && !result) {
      // Save to server every 30 seconds
      fetch('/api/exams/' + examId + '/autosave', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          studentId, studentName,
          answers: selectedAnswers
        })
      }).catch(function() {}); // Silent fail — localStorage is the primary store
    }
  }, 30000);
}

function restoreServerAutoSave() {
  // Skip if this is a fresh retake
  if (localStorage.getItem('cbt_retake_' + examId) === 'true') {
    try { localStorage.removeItem('cbt_retake_' + examId); } catch(e) {}
    return;
  }
  if (Object.keys(selectedAnswers).length > 0) return; // Already have local answers
  fetch('/api/exams/' + examId + '/autosave/load', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ studentId })
  }).then(function(r) { return r.json(); }).then(function(data) {
    if (data.success && data.autosave && data.autosave.answers) {
      const serverAnswers = data.autosave.answers;
      const serverCount = Object.keys(serverAnswers).length;
      if (serverCount > Object.keys(selectedAnswers).length) {
        selectedAnswers = serverAnswers;
        buildNavGrid();
        updateAnsweredCount();
        renderQuestion();
      }
    }
  }).catch(function() {});
}

function showAlreadySubmitted() {
  submitted = true;
  isExamActive = false;
  submitting = true;
  document.getElementById('exam-active').classList.add('hidden');
  const headerControls = document.getElementById('header-controls');
  headerControls.innerHTML = '<a href="{{ route("student.dashboard") }}" class="px-5 py-2 text-xs font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition-all">Return to Dashboard</a>';

  // Load attempt history
  try {
    const hist = localStorage.getItem('brain_history_' + examId);
    if (hist) attempts = JSON.parse(hist);
  } catch(e) {}
  const bestScore = attempts.length > 0 ? Math.max(...attempts.map(a => a.percentage)) : 0;
  const avgScore = attempts.length > 0 ? Math.round(attempts.reduce((s, a) => s + a.percentage, 0) / attempts.length) : 0;

  const attemptsHtml = attempts.length === 0 ? '<p class="text-xs text-slate-400 italic">No previous attempts recorded.</p>' :
    '<div class="space-y-2 max-h-48 overflow-y-auto pr-1">' + attempts.map((a, i) =>
      '<div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl border border-slate-100">' +
        '<div><span class="font-extrabold text-sm text-slate-800">Attempt #' + (i + 1) + '</span>' +
          '<p class="text-[10px] text-slate-400">' + new Date(a.date).toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + (a.timeSpent ? ' &middot; ' + formatTime(a.timeSpent) : '') + '</p></div>' +
        '<span class="font-black uppercase py-1.5 px-3 rounded-lg text-xs ' + (a.percentage >= 50 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200') + '">' + a.percentage + '%</span>' +
      '</div>'
    ).join('') + '</div>';

  document.getElementById('exam-results').innerHTML = `
    <div class="p-8 sm:p-10 bg-white border border-slate-200 rounded-2xl sm:rounded-3xl text-center shadow-sm slide-up">
      <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      </div>
      <h2 class="text-xl sm:text-2xl font-black text-slate-800">Already Submitted</h2>
      <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">This exam has already been completed. You can retake it or view results in your dashboard.</p>
      <div class="mt-6 flex flex-wrap justify-center gap-3">
        <button onclick="handleRetake()" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition-all cursor-pointer flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          Retake Exam
        </button>
        <a href="{{ route("student.dashboard") }}" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all inline-block">Go to Dashboard</a>
      </div>
      ${attempts.length > 0 ? `
      <div class="mt-8 text-left max-w-md mx-auto">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Attempt History</h4>
        <div class="grid grid-cols-2 gap-2 mb-4">
          <div class="p-3 bg-slate-50 rounded-xl text-center border border-slate-100"><span class="text-[9px] text-slate-400 uppercase font-black block">Best</span><strong class="text-lg font-black text-slate-800">${bestScore}%</strong></div>
          <div class="p-3 bg-slate-50 rounded-xl text-center border border-slate-100"><span class="text-[9px] text-slate-400 uppercase font-black block">Average</span><strong class="text-lg font-black text-indigo-600">${avgScore}%</strong></div>
        </div>
        ${attemptsHtml}
      </div>` : ''}
    </div>`;
  document.getElementById('exam-results').classList.remove('hidden');
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(() => {});
  } else {
    document.exitFullscreen().catch(() => {});
  }
}

function triggerSubmit(force) {
  if (submitting || submitted) return;
  if (!force && !confirm('Are you sure you want to submit your exam? This action cannot be undone.')) return;
  submitting = true;
  isExamActive = false;

  // Clear timer and auto-save intervals
  if (timerInterval) clearInterval(timerInterval);
  if (autoSaveInterval) clearInterval(autoSaveInterval);

  const btn = document.getElementById('submit-btn');
  if (btn) { btn.textContent = 'Scoring metrics...'; btn.disabled = true; }
  const timeSpent = Math.min((examDuration * 60) - secondsLeft, examDuration * 60);
  const submissionId = 'sub_' + Date.now() + '_' + Math.random().toString(36).substring(2, 8);

  // Persist submitted flag immediately to prevent duplicate submissions
  try {
    localStorage.setItem('cbt_submitted_' + examId, 'true');
    sessionStorage.setItem('cbt_submitted_' + examId, 'true');
    localStorage.removeItem('cbt_progress_' + examId);
  } catch(e) {}

  fetch('/api/exams/' + examId + '/submit', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ studentId, studentName, answers: selectedAnswers, timeSpent, submissionId })
  }).then(r => r.json()).then(data => {
    if (data.success) {
      result = data.result;
      submitted = true;
      // Persist result for page refresh
      try { sessionStorage.setItem('cbt_postsubmit_' + examId, JSON.stringify(data.result)); } catch(e) {}
      showResults();
      saveAttempt(data.result, timeSpent);
      // Show time-expired notification if auto-submitted
      if (force && secondsLeft <= 0) {
        showTimeoutNotification();
      }
    } else {
      computeLocalResult(timeSpent);
    }
  }).catch(() => {
    computeLocalResult(timeSpent);
  });
}

function showTimeoutNotification() {
  const notification = document.createElement('div');
  notification.id = 'timeout-notification';
  notification.className = 'fixed top-4 right-4 z-50 max-w-sm p-4 bg-amber-50 border border-amber-200 rounded-xl shadow-lg slide-up';
  notification.innerHTML = `
    <div class="flex items-start gap-3">
      <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <div>
        <p class="font-bold text-sm text-amber-900">Time Expired</p>
        <p class="text-xs text-amber-700 mt-1">Your examination time has expired. Your answers have been submitted automatically. Your result and marked script are now available.</p>
      </div>
      <button onclick="this.parentElement.parentElement.remove()" class="text-amber-400 hover:text-amber-600 shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>`;
  document.body.appendChild(notification);
  setTimeout(function() {
    if (notification.parentElement) notification.remove();
  }, 15000);
}

function computeLocalResult(timeSpent) {
  let score = 0;
  let correct = 0;
  const failed = [];
  let totalMarks = 0;
  questions.forEach((q, i) => {
    const m = q.marks || examDefaultMarks;
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
  document.getElementById('cert-id').innerHTML = 'Verification Code: <strong class="text-slate-800 font-mono">' + result.id + '</strong>';

  // Result Slip
  document.getElementById('slip-meta').innerHTML = '<div><span class="text-slate-400 uppercase font-bold text-[9px]">Student</span><p class="text-sm font-extrabold text-slate-900">' + result.studentName + '</p><p class="text-[10px] text-slate-500">ID: ' + result.studentId + '</p></div><div class="sm:text-right"><span class="text-slate-400 uppercase font-bold text-[9px]">Assessment</span><p class="text-sm font-extrabold text-slate-900">' + examTitle + '</p><p class="text-[10px] text-slate-500">Subject: ' + examSubject + ' (' + examLevel + ')</p></div>';
  document.getElementById('slip-table-body').innerHTML = '<tr><td class="p-3 border border-slate-300">Total Questions</td><td class="p-3 border border-slate-300">' + result.totalQuestions + ' Questions</td><td class="p-3 border border-slate-300">' + result.correctAnswers + ' Correct</td><td class="p-3 border border-slate-300 text-indigo-700 font-extrabold">' + result.percentage + '%</td></tr><tr class="bg-slate-50/50"><td class="p-3 border border-slate-300">Time Utilized</td><td class="p-3 border border-slate-300">' + examDuration + ' Min max</td><td class="p-3 border border-slate-300">' + formatTime(result.timeSpent || 0) + '</td><td class="p-3 border border-slate-300"><span class="' + (isPassed ? 'text-emerald-600' : 'text-rose-600') + ' font-extrabold uppercase">' + (isPassed ? 'Pass' : 'Fail') + '</span></td></tr>';
  document.getElementById('slip-kpi').innerHTML = '<h3 class="font-extrabold text-xs uppercase border-b border-slate-200 pb-1 text-slate-700">Performance Summary</h3><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><div class="bg-slate-50 p-3 rounded-lg border border-slate-200"><span class="text-[9px] text-slate-400 uppercase font-bold block">Strongest Area</span><strong class="text-sm text-slate-800">' + strongest + '</strong></div><div class="bg-slate-50 p-3 rounded-lg border border-slate-200"><span class="text-[9px] text-slate-400 uppercase font-bold block">Needs Improvement</span><strong class="text-sm text-slate-800">' + weakest + '</strong></div></div>';
  document.getElementById('print-date').textContent = new Date().toLocaleString();

  // Attempt history HTML
  const attemptsHtml = attempts.length === 0 ? '<p class="text-xs text-slate-400 italic">First logged attempt.</p>' :
    '<div class="space-y-2 max-h-48 overflow-y-auto pr-1">' + attempts.map((a, i) =>
      '<div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl border border-slate-100">' +
        '<div><span class="font-extrabold text-sm text-slate-800">Attempt #' + (i + 1) + '</span>' +
          '<p class="text-[10px] text-slate-400">' + new Date(a.date).toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + (a.timeSpent ? ' &middot; ' + formatTime(a.timeSpent) : '') + '</p></div>' +
        '<span class="font-black uppercase py-1.5 px-3 rounded-lg text-xs ' + (a.percentage >= 50 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200') + '">' + a.percentage + '%</span>' +
      '</div>'
    ).join('') + '</div>';

  // Topic performance bars HTML
  const topicBars = Object.entries(topicStats).map(([tn, st]) => {
    const rate = Math.round((st.correct / st.total) * 100);
    const barColor = rate >= 75 ? 'bg-emerald-500' : rate >= 50 ? 'bg-indigo-500' : 'bg-rose-500';
    return '<div class="space-y-1.5"><div class="flex items-center justify-between gap-2 text-xs font-bold text-slate-700"><span class="break-words min-w-0">' + tn + '</span><span class="whitespace-nowrap shrink-0 text-slate-500">' + st.correct + '/' + st.total + ' (' + rate + '%)</span></div><div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden"><div class="h-full rounded-full transition-all duration-700 bar-animate ' + barColor + '" style="width:' + rate + '%"></div></div></div>';
  }).join('');

  // Explanations HTML
  const explanationsHtml = result.failedQuestions.map((item, idx) => {
    const isCorrect = item.selectedAnswer === item.correctAnswer;
    const isNotAnswered = !item.selectedAnswer;
    let badgeColor, badgeText, badgeIcon;
    if (isCorrect) { badgeColor = 'bg-emerald-50 text-emerald-700 border-emerald-200'; badgeText = 'Correct'; badgeIcon = '&#10004;'; }
    else if (isNotAnswered) { badgeColor = 'bg-amber-50 text-amber-700 border-amber-200'; badgeText = 'Not Answered'; badgeIcon = '—'; }
    else { badgeColor = 'bg-rose-50 text-rose-700 border-rose-200'; badgeText = 'Wrong'; badgeIcon = '&#10008;'; }
    const rOptA = item.optionA || (item.options && item.options.A) || item.A || '';
    const rOptB = item.optionB || (item.options && item.options.B) || item.B || '';
    const rOptC = item.optionC || (item.options && item.options.C) || item.C || '';
    const rOptD = item.optionD || (item.options && item.options.D) || item.D || '';
    const opts = [
      { key: 'A', label: rOptA }, { key: 'B', label: rOptB },
      { key: 'C', label: rOptC }, { key: 'D', label: rOptD }
    ];
    return '<div class="border border-slate-200 rounded-2xl p-4 sm:p-5 space-y-4 fade-in bg-white">' +
      '<div class="flex items-center justify-between gap-2 flex-wrap">' +
        '<span class="text-xs bg-slate-100 text-slate-600 py-1 px-3 rounded-full font-extrabold font-mono">Question ' + String(idx + 1).padStart(2, '0') + '</span>' +
        '<span class="text-[10px] uppercase font-black tracking-wide border py-1 px-3 rounded-full ' + badgeColor + '">' + badgeIcon + ' ' + badgeText + '</span>' +
      '</div>' +
      '<p class="text-sm sm:text-base font-bold text-slate-800 leading-relaxed break-words">' + item.question + '</p>' +
      '<div class="grid grid-cols-1 sm:grid-cols-2 gap-2">' +
      opts.map(opt => {
        const isCorrectOpt = opt.key === item.correctAnswer;
        const isSelectedOpt = opt.key === item.selectedAnswer;
        let borderStyle = 'border-slate-200 bg-white';
        let markerColor = 'bg-slate-200 text-slate-700 border-slate-300';
        let badge = '';
        if (isCorrectOpt) { borderStyle = 'border-emerald-300 bg-emerald-50/50 ring-1 ring-emerald-200'; markerColor = 'bg-emerald-500 text-white border-emerald-500'; badge = '<span class="text-[10px] font-extrabold text-emerald-600 shrink-0 ml-auto">&#10004; Correct</span>'; }
        else if (isSelectedOpt && !isCorrectOpt) { borderStyle = 'border-rose-300 bg-rose-50/50 ring-1 ring-rose-200'; markerColor = 'bg-rose-500 text-white border-rose-500'; badge = '<span class="text-[10px] font-extrabold text-rose-600 shrink-0 ml-auto">&#10008; Your Answer</span>'; }
        return '<div class="p-3 border rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2.5 transition-all ' + borderStyle + '"><span class="w-7 h-7 rounded-lg flex items-center justify-center font-mono font-bold shrink-0 text-xs shadow-xs border ' + markerColor + '">' + opt.key + '</span><span class="break-words min-w-0 flex-1 leading-snug">' + opt.label + '</span>' + badge + '</div>';
      }).join('') + '</div>' +
      '<div class="p-4 bg-indigo-50/60 border border-indigo-100 rounded-xl flex items-start gap-3 mt-1"><svg class="w-5 h-5 text-indigo-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><div class="text-xs sm:text-sm text-slate-700 leading-relaxed font-medium break-words min-w-0"><strong class="text-indigo-700 font-extrabold">Explanation:</strong> ' + (item.explanation || 'The correct answer is Option ' + item.correctAnswer + '.') + '</div></div>' +
    '</div>';
  }).join('');

  // Graded script HTML (for printable download)
  const gradedScriptHtml = `
    <div class="border-b-2 border-slate-900 pb-4 mb-6">
      <h1 class="text-xl font-black uppercase text-slate-900">${examTitle}</h1>
      <p class="text-xs uppercase font-extrabold text-slate-500">${examSubject} &bull; Graded Script</p>
      <p class="text-xs text-slate-400 mt-1">Student: ${result.studentName} &bull; Score: ${result.score}/${result.totalPossibleMarks} (${result.percentage}%) &bull; Date: ${new Date(result.date).toLocaleDateString()}</p>
    </div>
    ${result.failedQuestions.map((item, idx) => {
      const isCorrect = item.selectedAnswer === item.correctAnswer;
      const isNotAnswered = !item.selectedAnswer;
      const statusBadge = isCorrect ? '<span style="color:#059669;font-weight:800">&#10004; Correct</span>' : (isNotAnswered ? '<span style="color:#d97706;font-weight:800">— Not Answered</span>' : '<span style="color:#e11d48;font-weight:800">&#10008; Wrong</span>');
      const optLabels = [
        { k: 'A', v: item.optionA || (item.options?.A) || item.A || '' },
        { k: 'B', v: item.optionB || (item.options?.B) || item.B || '' },
        { k: 'C', v: item.optionC || (item.options?.C) || item.C || '' },
        { k: 'D', v: item.optionD || (item.options?.D) || item.D || '' },
      ];
      return '<div style="border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-bottom:12px;page-break-inside:avoid">' +
        '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">' +
          '<span style="font-size:11px;font-weight:800;color:#64748b">Question ' + (idx + 1) + '</span>' +
          statusBadge +
        '</div>' +
        '<p style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:8px;line-height:1.4">' + item.question + '</p>' +
        '<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">' +
        optLabels.map(opt => {
          const isCorrectOpt = opt.k === item.correctAnswer;
          const isSelectedOpt = opt.k === item.selectedAnswer;
          let bg = '#fff', border = '#e2e8f0', marker = '#f1f5f9';
          if (isCorrectOpt) { bg = '#f0fdf4'; border = '#86efac'; marker = '#22c55e'; }
          else if (isSelectedOpt && !isCorrectOpt) { bg = '#fff1f2'; border = '#fda4af'; marker = '#e11d48'; }
          const mark = isCorrectOpt ? ' &#10004;' : (isSelectedOpt && !isCorrectOpt ? ' &#10008;' : '');
          const markColor = isCorrectOpt ? '#059669' : (isSelectedOpt && !isCorrectOpt ? '#e11d48' : 'transparent');
          return '<div style="padding:6px 10px;border:1px solid ' + border + ';border-radius:6px;background:' + bg + ';font-size:12px;font-weight:600;color:#334155;display:flex;align-items:center;gap:6px">' +
            '<span style="width:20px;height:20px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:#fff;background:' + marker + ';border:1px solid ' + border + '">' + opt.k + '</span>' +
            '<span style="flex:1">' + opt.v + '</span>' +
            (mark ? '<span style="font-size:11px;font-weight:800;color:' + markColor + '">' + mark + '</span>' : '') +
          '</div>';
        }).join('') + '</div>' +
        '<div style="margin-top:8px;padding:8px 12px;background:#eef2ff;border:1px solid #e0e7ff;border-radius:6px;font-size:11px;color:#334155;line-height:1.4"><strong style="color:#4338ca">Explanation:</strong> ' + (item.explanation || 'The correct answer is Option ' + item.correctAnswer + '.') + '</div>' +
      '</div>';
    }).join('')}
  `;
  document.getElementById('graded-script-content').innerHTML = gradedScriptHtml;

  const resultContainer = document.getElementById('exam-results');
  resultContainer.innerHTML = `
    <!-- Hero Score Banner -->
    <div class="p-6 sm:p-8 bg-gradient-to-br from-indigo-700 via-indigo-800 to-indigo-900 rounded-2xl sm:rounded-3xl text-white shadow-xl relative overflow-hidden print-hidden slide-up">
      <div class="absolute inset-0 bg-[radial-gradient(#ffffff0a_2px,transparent_2px)]" style="background-size:20px 20px;pointer-events:none"></div>
      <div class="relative z-10">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
          <div class="space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/10 rounded-full text-xs font-bold text-indigo-200 border border-white/5">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
              Exam Complete
            </div>
            <h2 class="text-2xl sm:text-3xl font-black">${result.percentage >= 50 ? 'Well Done! Excellent Performance' : 'Keep Practicing!'}</h2>
            <p class="text-indigo-200 text-xs sm:text-sm max-w-lg font-medium leading-relaxed">Student <strong class="text-white font-extrabold">${result.studentName}</strong> completed <strong class="text-white font-extrabold">${examTitle}</strong>. Review the breakdown and download your results below.</p>
          </div>
          <div class="bg-white/10 backdrop-blur-md rounded-2xl p-5 sm:p-6 border border-white/10 shrink-0 text-center min-w-[160px]">
            <span class="text-[10px] uppercase tracking-widest text-indigo-200 font-extrabold block mb-1">Score</span>
            <p class="text-4xl sm:text-5xl font-black text-white">${result.percentage}%</p>
            <span class="text-[10px] mt-2 inline-block px-3 py-1 ${result.percentage >= 50 ? 'bg-emerald-400/20 text-emerald-300' : 'bg-amber-400/20 text-amber-300'} rounded-full font-bold uppercase">${result.percentage >= 50 ? 'Passed' : 'Retake Required'}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 result-card">
      <div class="p-4 sm:p-5 bg-white border border-slate-200 rounded-xl sm:rounded-2xl shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="min-w-0"><span class="text-[10px] text-slate-400 uppercase font-bold block tracking-wider">Score</span><strong class="text-lg font-black text-slate-800">${result.score}/${result.totalPossibleMarks}</strong></div>
        </div>
      </div>
      <div class="p-4 sm:p-5 bg-white border border-slate-200 rounded-xl sm:rounded-2xl shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="min-w-0"><span class="text-[10px] text-slate-400 uppercase font-bold block tracking-wider">Time</span><strong class="text-lg font-black text-slate-800">${formatTime(result.timeSpent || ((examDuration * 60) - secondsLeft))}</strong></div>
        </div>
      </div>
      <div class="p-4 sm:p-5 bg-white border border-slate-200 rounded-xl sm:rounded-2xl shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
          <div class="min-w-0"><span class="text-[10px] text-slate-400 uppercase font-bold block tracking-wider">Date</span><strong class="text-sm font-black text-slate-800">${new Date(result.date).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}</strong></div>
        </div>
      </div>
      <div class="p-4 sm:p-5 bg-white border border-slate-200 rounded-xl sm:rounded-2xl shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
          </div>
          <div class="min-w-0"><span class="text-[10px] text-slate-400 uppercase font-bold block tracking-wider">Correct</span><strong class="text-lg font-black text-slate-800">${result.correctAnswers}/${result.totalQuestions}</strong></div>
        </div>
      </div>
    </div>

    <!-- Download / Action Buttons -->
    <div class="p-5 sm:p-6 bg-white border border-slate-200 rounded-2xl sm:rounded-3xl shadow-xs print-hidden slide-up">
      <div class="flex flex-wrap items-center gap-3">
        <button onclick="window.open('/api/download/graded-script/${examId}/${result.id}', '_blank')" class="px-5 py-3 bg-gradient-to-r from-amber-500 to-emerald-600 hover:from-amber-600 hover:to-emerald-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-lg flex items-center gap-2 cursor-pointer ring-2 ring-emerald-300">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Download Full Result (PDF)
        </button>
        <button onclick="handlePrintCertificate()" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.032-.133-2.052-.382-3.016z"/></svg>
          Print Certificate
        </button>
        <button onclick="handlePrintResultSlip()" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Download Result Slip (PDF)
        </button>
        <button onclick="window.open('/api/download/graded-script/${examId}/${result.id}', '_blank')" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
          Download Graded Script (PDF)
        </button>
        <button onclick="window.open('/api/download/exam/${examId}/pdf', '_blank')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
          Download Exam Script (PDF)
        </button>
        <button onclick="window.open('/api/download/exam/${examId}/docx', '_blank')" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Download Exam Script (Word)
        </button>
        <button onclick="handleRetake()" class="px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-extrabold text-xs rounded-xl transition-all flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          Retake Exam
        </button>
        <a href="{{ route("student.dashboard") }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold text-xs rounded-xl transition-all flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          Dashboard
        </a>
      </div>
    </div>

    <!-- Analytics Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 print-hidden">
      <!-- Attempt History -->
      <div class="p-4 sm:p-6 bg-white border border-slate-200 rounded-xl sm:rounded-2xl shadow-xs space-y-4 result-card">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
          <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          Attempt History
        </h4>
        <div class="grid grid-cols-2 gap-2">
          <div class="p-3 bg-slate-50 rounded-xl text-center border border-slate-100"><span class="text-[9px] text-slate-400 uppercase font-black block">Best</span><strong class="text-lg font-black text-slate-800">${bestScore}%</strong></div>
          <div class="p-3 bg-slate-50 rounded-xl text-center border border-slate-100"><span class="text-[9px] text-slate-400 uppercase font-black block">Average</span><strong class="text-lg font-black text-indigo-600">${avgScore}%</strong></div>
        </div>
        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider pt-2 border-t border-slate-100">Previous Attempts (${attempts.length})</div>
        ${attemptsHtml}
      </div>

      <!-- Analytics & Topic Performance -->
      <div class="lg:col-span-2 space-y-4 sm:space-y-6">
        <div class="p-4 sm:p-6 bg-white border border-slate-200 rounded-xl sm:rounded-2xl shadow-xs space-y-4 result-card">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            Subject Analytics
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
            <div class="p-4 bg-emerald-50/60 rounded-xl border border-emerald-100"><span class="text-[10px] text-emerald-700 uppercase font-black tracking-wider block">Strongest Area</span><strong class="text-sm font-black text-slate-800 mt-1 block">${strongest}</strong><p class="text-[10px] text-slate-400 mt-1 font-medium">Top performance in this area.</p></div>
            <div class="p-4 bg-rose-50/60 rounded-xl border border-rose-100"><span class="text-[10px] text-rose-600 uppercase font-black tracking-wider block">Needs Revision</span><strong class="text-sm font-black text-slate-800 mt-1 block">${weakest}</strong><p class="text-[10px] text-slate-400 mt-1 font-medium">Focus more study here.</p></div>
          </div>
          <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-100 text-xs">
            <div><span class="text-slate-400 font-extrabold uppercase text-[9px] tracking-wider block mb-1">Attempted</span><p class="text-base font-black text-slate-800">${qAttempted} of ${questions.length}</p></div>
            <div><span class="text-slate-400 font-extrabold uppercase text-[9px] tracking-wider block mb-1">Unanswered</span><p class="text-base font-black ${qSkipped > 0 ? 'text-amber-600' : 'text-slate-500'}">${qSkipped}</p></div>
          </div>
        </div>

        <!-- Topic Performance Bars -->
        <div class="p-4 sm:p-6 bg-white border border-slate-200 rounded-xl sm:rounded-2xl shadow-xs space-y-4 result-card">
          <h4 class="text-xs font-extrabold uppercase tracking-widest text-slate-500 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Topic Performance
          </h4>
          <div class="space-y-3.5">
            ${topicBars}
          </div>
        </div>
      </div>
    </div>

    <!-- Review Answers & Explanations -->
    <div class="p-5 sm:p-7 bg-white border border-slate-200 rounded-2xl sm:rounded-3xl shadow-sm space-y-6 print-hidden slide-up">
      <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
          <h3 class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Review Answers & Explanations
          </h3>
          <p class="text-xs text-slate-400 font-bold mt-1">Review each question, your answer, the correct answer, and the explanation.</p>
        </div>
        <span class="text-xs bg-slate-100 text-slate-600 py-1.5 px-3.5 rounded-full font-extrabold">${result.correctAnswers}/${result.totalQuestions} Correct</span>
      </div>
      <div class="space-y-4 sm:space-y-5">
        ${explanationsHtml}
      </div>
      <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-200 mt-6">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mr-2">Download:</span>
        <button onclick="window.open('/api/download/graded-script/${examId}/${result.id}', '_blank')" class="px-4 py-2.5 bg-gradient-to-r from-amber-500 to-emerald-600 hover:from-amber-600 hover:to-emerald-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Full Result (PDF)
        </button>
        <button onclick="window.open('/api/download/graded-script/${examId}/${result.id}', '_blank')" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
          Graded Script (PDF)
        </button>
        <button onclick="window.open('/api/download/exam/${examId}/docx', '_blank')" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          DOCX
        </button>
        <button onclick="window.print()" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          Print
        </button>
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
  alert('To Save PDF Certificate:\n1. Change destination to \'Save as PDF\'.\n2. Set Layout to \'Landscape\'.\n3. Ensure background graphics are ON (under More Settings).');
  window.print();
}

function handlePrintResultSlip() {
  alert('To Save as PDF:\n1. Change destination to \'Save as PDF\'.\n2. Set Layout to \'Portrait\'.\n3. Click Save.');
  window.print();
}

function handleRetake() {
  if (confirm('Are you sure you want to retake this exam? This will reset your current timer and answers.')) {
    // Clear all persistent flags
    try {
      localStorage.removeItem('cbt_progress_' + examId);
      localStorage.removeItem('cbt_submitted_' + examId);
      sessionStorage.removeItem('cbt_submitted_' + examId);
      sessionStorage.removeItem('cbt_postsubmit_' + examId);
      localStorage.setItem('cbt_retake_' + examId, 'true');
    } catch(e) {}
    // Clear server auto-save from previous attempt
    fetch('/api/exams/' + examId + '/autosave', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ studentId, answers: {} })
    }).catch(function() {});
    selectedAnswers = {};
    flaggedQuestions = {};
    secondsLeft = examDuration * 60;
    endTime = Date.now() + secondsLeft * 1000;
    isExamActive = true;
    result = null;
    submitted = false;
    currentIndex = 0;
    submitting = false;
    if (timerInterval) clearInterval(timerInterval);
    if (autoSaveInterval) clearInterval(autoSaveInterval);
    document.getElementById('exam-results').classList.add('hidden');
    document.getElementById('exam-results').innerHTML = '';
    document.getElementById('exam-active').classList.remove('hidden');
    document.getElementById('submit-btn').classList.remove('hidden');
    document.getElementById('submit-btn').disabled = false;
    document.getElementById('submit-btn').textContent = 'Finish & Submit';
    document.getElementById('header-controls').innerHTML = `
      <button onclick="toggleFullscreen()" class="p-2 text-slate-700 hover:text-slate-900 bg-slate-200 hover:bg-slate-300 rounded-xl transition" title="Toggle Fullscreen Mode">
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
  if (isExamActive && !result && !submitted) {
    e.preventDefault();
    e.returnValue = 'Warning: Leaving or refreshing this page will abort your ongoing CBT session. Your progress will be saved where you left off.';
  }
});
</script>
@endsection
