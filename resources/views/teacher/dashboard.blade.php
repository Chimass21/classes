@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-white">
    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-6">
        {{-- Header --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Educator Dashboard</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900">Teacher Portal</h1>
                <p class="text-sm text-slate-500">Generate curriculum-based lesson plans, notes, and CBT exams</p>
            </div>
            <div class="flex items-center gap-2">
                @if(Session::get('user._switched'))
                    <form action="{{ route('switch.back') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 bg-amber-50 text-amber-700 rounded-lg text-xs font-bold hover:bg-amber-100 transition cursor-pointer">
                            ⬅ Back to {{ ucfirst(Session::get('user._original_role')) }}
                        </button>
                    </form>
                @else
                    <form action="{{ route('switch.to.student') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-bold hover:bg-indigo-100 transition cursor-pointer">
                            👤 Student Portal
                        </button>
                    </form>
                @endif
                <span class="px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold">{{ Session::get('user.name') }}</span>
                <button onclick="loadTeacherData()" class="p-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-slate-600 transition cursor-pointer" title="Refresh">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        </div>

        {{-- Loading --}}
        <div id="loading" class="py-20 text-center">
            <div class="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
            <p class="text-sm text-slate-400 mt-3 font-medium">Loading dashboard...</p>
        </div>

        {{-- Content --}}
        <div id="content" class="hidden space-y-6">

            {{-- Tabs --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 overflow-x-auto">
                    <div class="flex min-w-max">
                        <button onclick="switchTab('lesson-planner')" id="tab-lesson-planner-btn" class="tab-btn px-4 py-3 text-sm font-semibold border-b-2 border-indigo-600 text-indigo-700 bg-white transition cursor-pointer whitespace-nowrap">Lesson Planner</button>
                        <button onclick="switchTab('lesson-notes')" id="tab-lesson-notes-btn" class="tab-btn px-4 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition cursor-pointer whitespace-nowrap">Lesson Notes</button>
                        <button onclick="switchTab('questions')" id="tab-questions-btn" class="tab-btn px-4 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition cursor-pointer whitespace-nowrap">Question Pool</button>
                        <button onclick="switchTab('cbt-engine')" id="tab-cbt-engine-btn" class="tab-btn px-4 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition cursor-pointer whitespace-nowrap">CBT Engine</button>
                        <button onclick="switchTab('results')" id="tab-results-btn" class="tab-btn px-4 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition cursor-pointer whitespace-nowrap">Results</button>
                    </div>
                </div>

                {{-- === LESSON PLANNER TAB === --}}
                <div id="tab-lesson-planner" class="tab-panel p-5">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h3 class="text-lg font-bold text-slate-900">Generate Curriculum Lesson Plan</h3>
                            <p class="text-sm text-slate-500">Create professional Nigerian curriculum-based lesson plans in tabular A4 format.</p>
                            <form id="lesson-plan-form" class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Subject</label>
                                        <select id="plan-subject" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                                            <option value="">Select subject...</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Class</label>
                                        <select id="plan-class" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Term</label>
                                        <select id="plan-term" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Week</label>
                                        <select id="plan-week" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Duration</label>
                                        <input type="text" id="plan-duration" value="40 Minutes" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600 block mb-1">Topic</label>
                                    <input type="text" id="plan-topic" required placeholder="e.g., Addition of Whole Numbers" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">School Name</label>
                                        <input type="text" id="plan-school" value="ClassPortal Academy" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Teacher's Name</label>
                                        <input type="text" id="plan-teacher" value="{{ Session::get('user.name') }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                                    </div>
                                </div>
                                <button type="submit" id="plan-submit-btn" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-lg transition cursor-pointer">Generate Lesson Plan</button>
                            </form>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 mb-3">Saved Lesson Plans</h4>
                            <div id="plans-list" class="space-y-2 max-h-[500px] overflow-y-auto">
                                <div class="text-center py-8 text-sm text-slate-400">No lesson plans yet. Generate one!</div>
                            </div>
                        </div>
                    </div>

                    {{-- Lesson Plan Preview --}}
                    <div id="plan-preview" class="hidden mt-6 bg-white border border-slate-200 rounded-xl p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-4 border-b border-slate-200 pb-4">
                            <h3 class="text-lg font-bold text-slate-900">Lesson Plan Preview</h3>
                            <div class="flex flex-wrap gap-2" id="plan-action-buttons"></div>
                        </div>
                        <div id="plan-content" class="overflow-x-auto"></div>
                    </div>
                </div>

                {{-- === LESSON NOTES TAB === --}}
                <div id="tab-lesson-notes" class="tab-panel p-5 hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h3 class="text-lg font-bold text-slate-900">Generate Lesson Note</h3>
                            <p class="text-sm text-slate-500">Create detailed, comprehensive lesson notes aligned with the Nigerian curriculum.</p>
                            <form id="lesson-note-form" class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Subject</label>
                                        <select id="note-subject" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Class</label>
                                        <select id="note-class" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Term</label>
                                        <select id="note-term" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Week</label>
                                        <select id="note-week" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Periods</label>
                                        <select id="note-periods" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none">
                                            <option>1 Period</option><option selected>2 Periods</option><option>3 Periods</option><option>4 Periods</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600 block mb-1">Topic</label>
                                    <input type="text" id="note-topic" required placeholder="e.g., Fractions and Decimals" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Difficulty</label>
                                        <select id="note-difficulty" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                                            <option>Easy</option><option selected>Medium</option><option>Hard</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" id="note-submit-btn" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-lg transition cursor-pointer">Generate Lesson Note</button>
                            </form>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 mb-3">Saved Lesson Notes</h4>
                            <div id="notes-list" class="space-y-2 max-h-[500px] overflow-y-auto"></div>
                        </div>
                    </div>

                </div>

                {{-- === QUESTION POOL TAB === --}}
                <div id="tab-questions" class="tab-panel p-5 hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h3 class="text-lg font-bold text-slate-900">Generate Questions</h3>
                            <p class="text-sm text-slate-500">Create curriculum-aligned objective and theory questions for exams and practice.</p>
                            <form id="questions-form" class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Subject</label>
                                        <select id="q-subject" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Class</label>
                                        <select id="q-class" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none"></select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Term</label>
                                        <select id="q-term" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none"></select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Week</label>
                                        <select id="q-week" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none"></select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Count</label>
                                        <select id="q-count" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                                            <option value="10">10 Questions</option>
                                            <option value="20" selected>20 Questions</option>
                                            <option value="30">30 Questions</option>
                                            <option value="50">50 Questions</option>
                                            <option value="100">100 Questions</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600 block mb-1">Topic</label>
                                    <input type="text" id="q-topic" required placeholder="e.g., Algebra" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="q-theory" class="rounded border-slate-300">
                                    <label for="q-theory" class="text-sm text-slate-700">Include Theory / Essay / Structured Questions</label>
                                </div>
                                <button type="submit" id="q-submit-btn" class="w-full py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-sm rounded-lg transition cursor-pointer">Generate Questions</button>
                            </form>
                            <div id="q-save-section" class="hidden p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                                <p class="text-sm text-emerald-800 font-medium" id="q-save-msg"></p>
                                <div class="flex gap-2 mt-2">
                                    <button onclick="saveQuestions()" class="px-3 py-1.5 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700 cursor-pointer">Save to Pool</button>
                                    <button onclick="convertToCBT()" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-bold rounded-lg hover:bg-indigo-700 cursor-pointer">Convert to CBT</button>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 mb-3">Saved Question Sets</h4>
                            <div id="q-sets-list" class="space-y-2 max-h-[500px] overflow-y-auto"></div>
                        </div>
                    </div>

                    {{-- Questions Preview --}}
                    <div id="q-preview" class="hidden mt-6 bg-white border border-slate-200 rounded-xl p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-4 border-b border-slate-200 pb-4">
                            <h3 class="text-lg font-bold text-slate-900">Generated Questions</h3>
                            <div class="flex flex-wrap gap-2" id="q-action-buttons"></div>
                        </div>
                        <div id="q-content"></div>
                    </div>
                </div>

                {{-- === CBT ENGINE TAB === --}}
                <div id="tab-cbt-engine" class="tab-panel p-5 hidden">
                    <div class="space-y-6">
                        <div class="bg-white border border-slate-200 rounded-xl p-5">
                            <h3 class="text-lg font-bold text-slate-900 mb-4">CBT Exam Manager</h3>
                            <div id="cbt-exams-list" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="text-center py-8 text-sm text-slate-400 col-span-2">No exams created yet.</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- === RESULTS TAB === --}}
                <div id="tab-results" class="tab-panel p-5 hidden">
                    <div class="space-y-4">
                        <h3 class="text-lg font-bold text-slate-900">Student Results</h3>
                        <div id="results-list" class="space-y-2"></div>
                    </div>
                </div>

                {{-- Persistent previews (always visible) --}}
                <div id="note-preview" class="hidden mt-6 bg-white border border-slate-200 rounded-xl p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4 border-b border-slate-200 pb-4">
                        <h3 class="text-lg font-bold text-slate-900">Lesson Note Preview</h3>
                        <div class="flex flex-wrap gap-2" id="note-action-buttons"></div>
                    </div>
                    <div id="note-content" class="prose max-w-none text-sm"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let teacherData = { plans: [], notes: [], exams: [], results: [], questionSets: [] };
let currentPlanId = null, currentNoteId = null, currentQsId = null;
let currentQuestions = null;

// ====== CURRICULUM DATA LOADING ======
async function loadCurriculumSelects() {
    try {
        const [subRes, clsRes, termRes, weekRes] = await Promise.all([
            fetch('/api/subjects').then(r => r.json()),
            fetch('/api/curriculum/classes').then(r => r.json()),
            fetch('/api/curriculum/terms').then(r => r.json()),
            fetch('/api/curriculum/weeks').then(r => r.json()),
        ]);
        const subjects = subRes.subjects || [];
        const classes = clsRes.classes || [];
        const terms = termRes.terms || [];
        const weeks = weekRes.weeks || [];

        document.querySelectorAll('select[id$="-subject"], select[id$="-subject"], #q-subject, #plan-subject, #note-subject').forEach(sel => {
            sel.innerHTML = '<option value="">Select subject...</option>' + subjects.map(s => `<option value="${s}">${s}</option>`).join('');
        });
        document.querySelectorAll('#plan-class, #note-class, #q-class').forEach(sel => {
            sel.innerHTML = classes.map(c => `<option value="${c}">${c}</option>`).join('');
        });
        document.querySelectorAll('#plan-term, #note-term, #q-term').forEach(sel => {
            sel.innerHTML = terms.map(t => `<option value="${t}">${t}</option>`).join('');
        });
        document.querySelectorAll('#plan-week, #note-week, #q-week').forEach(sel => {
            sel.innerHTML = weeks.map(w => `<option value="${w}">Week ${w}</option>`).join('');
        });
    } catch(e) { console.error('Failed to load curriculum data:', e); }
}

// ====== DATA LOADING ======
async function loadTeacherData() {
    document.getElementById('loading').classList.remove('hidden');
    document.getElementById('content').classList.add('hidden');
    try {
        const [statsRes] = await Promise.all([
            fetch('/api/admin/stats').then(r => r.json()).catch(() => ({ users: [], exams: [], results: [], lessonNotes: [], lessonPlans: [], questionSets: [] })),
        ]);
        const user = await fetch('/api/auth/session').then(r => r.json()).then(d => d.user);
        const userId = user?.id || '';
        teacherData.plans = (statsRes.lessonPlans || []).filter(p => p.teacherId === userId);
        teacherData.notes = (statsRes.lessonNotes || []).filter(n => n.teacherId === userId);
        teacherData.exams = (statsRes.exams || []).filter(e => e.creatorId === userId);
        teacherData.results = (statsRes.results || []).filter(r => r.studentId);
        renderPlans();
        renderNotes();
        renderExams();
        renderResults();

        // Auto-display the most recent lesson note in the preview
        if (teacherData.notes.length > 0) {
            const last = teacherData.notes[teacherData.notes.length - 1];
            currentNoteId = last.id;
            displayLessonNote(last);
        }

        document.getElementById('loading').classList.add('hidden');
        document.getElementById('content').classList.remove('hidden');
    } catch(e) {
        document.getElementById('loading').innerHTML = '<p class="text-sm text-red-600 font-medium">Failed to load. <button onclick="loadTeacherData()" class="underline cursor-pointer">Retry</button></p>';
    }
}

// ====== LESSON PLAN ======
document.getElementById('lesson-plan-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('plan-submit-btn');
    btn.disabled = true; btn.textContent = 'Generating...';
    try {
        const res = await fetch('/api/ai/lesson-plan', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                subject: document.getElementById('plan-subject').value,
                class: document.getElementById('plan-class').value,
                term: document.getElementById('plan-term').value,
                week: parseInt(document.getElementById('plan-week').value),
                topic: document.getElementById('plan-topic').value,
                schoolName: document.getElementById('plan-school').value,
                teacherName: document.getElementById('plan-teacher').value,
                duration: document.getElementById('plan-duration').value,
            })
        });
        const data = await res.json();
        if (data.success) {
            currentPlanId = data.planId;
            displayLessonPlan(data.plan);
            loadTeacherData();
        } else {
            alert(data.error || 'Generation failed.');
        }
    } catch(e) { alert('Network error.'); }
    finally { btn.disabled = false; btn.textContent = 'Generate Lesson Plan'; }
});

function displayLessonPlan(plan) {
    document.getElementById('plan-preview').classList.remove('hidden');
    const container = document.getElementById('plan-content');
    const steps = plan.lessonSteps || [];
    const objectives = plan.behaviouralObjectives || [];
    const materials = plan.instructionalMaterials || [];

    const stepsHtml = steps.map(s => `<tr>
        <td style="padding:1px 2px;border:1px solid #000;font-size:7pt;font-weight:700;vertical-align:top;text-align:center">${s.step || ''}</td>
        <td style="padding:1px 2px;border:1px solid #000;font-size:7pt;vertical-align:top">${s.teacherActivities || ''}</td>
        <td style="padding:1px 2px;border:1px solid #000;font-size:7pt;vertical-align:top">${s.learnerActivities || ''}</td>
        <td style="padding:1px 2px;border:1px solid #000;font-size:7pt;vertical-align:top">${s.learningPoints || ''}</td>
    </tr>`).join('');

    const stepsHeader = `<tr>
        <th style="padding:1px 2px;border:1px solid #000;font-size:7pt;font-weight:700;text-align:left;background:#1a56db;color:#fff">Step</th>
        <th style="padding:1px 2px;border:1px solid #000;font-size:7pt;font-weight:700;text-align:left;background:#1a56db;color:#fff">Teacher's Activities</th>
        <th style="padding:1px 2px;border:1px solid #000;font-size:7pt;font-weight:700;text-align:left;background:#1a56db;color:#fff">Learners' Activities</th>
        <th style="padding:1px 2px;border:1px solid #000;font-size:7pt;font-weight:700;text-align:left;background:#1a56db;color:#fff">Learning Points</th>
    </tr>`;

    const objItems = objectives.map(o => `<tr><td style="padding:0 2px;font-size:7.5pt;border:1px solid #000" colspan="4">${o}</td></tr>`).join('');
    const matItems = materials.length ? `<tr><td style="padding:1px 2px;font-size:7pt;border:1px solid #000" colspan="4">${materials.join('; ')}</td></tr>` : '';

    const remarksHtml = `<tr>
        <td style="padding:1px 2px;border:1px solid #000;font-size:7pt;font-weight:700;width:12%">Remarks</td>
        <td style="padding:1px 2px;border:1px solid #000;font-size:7pt" colspan="3"></td>
    </tr>`;

    const dateStr = new Date().toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
    const sigHtml = `<tr>
        <td style="padding:1px 2px;border:1px solid #000;font-size:7.5pt;width:25%"><b>Teacher's Signature:</b> _______________</td>
        <td style="padding:1px 2px;border:1px solid #000;font-size:7.5pt;width:25%"><b>Date:</b> _______________</td>
        <td style="padding:1px 2px;border:1px solid #000;font-size:7.5pt;width:25%"><b>Head Teacher's Signature:</b> _______________</td>
        <td style="padding:1px 2px;border:1px solid #000;font-size:7.5pt;width:25%"><b>Date:</b> _______________</td>
    </tr>`;

    container.innerHTML = `
    <style>
        .lp-table { width:100%; border-collapse:collapse; font-family:Arial,sans-serif; }
        .lp-table td, .lp-table th { border:1px solid #000; }
        .lp-container { max-width:210mm; margin:0 auto; background:#fff; page-break-inside:avoid; break-inside:avoid; }
        @media print {
            .lp-container { max-width:100%; margin:0; padding:0; }
            .lp-table { font-size:7pt !important; }
            .lp-table td, .lp-table th { padding:1px 2px !important; }
            .no-print { display:none !important; }
        }
    </style>
    <div class="lp-container">
        <table class="lp-table" style="font-size:7.5pt">
            <tr>
                <th colspan="4" style="padding:3px;font-size:9pt;font-weight:700;text-align:center;background:#1a56db;color:#fff;border:1px solid #000">LESSON PLAN</th>
            </tr>
            <tr>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700;width:12%">School:</td>
                <td style="padding:1px 3px;font-size:7.5pt;width:38%">${plan.schoolName || ''}</td>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700;width:12%">Teacher:</td>
                <td style="padding:1px 3px;font-size:7.5pt;width:38%">${plan.teacherName || ''}</td>
            </tr>
            <tr>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700">Subject:</td>
                <td style="padding:1px 3px;font-size:7.5pt">${plan.subject || ''}</td>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700">Class:</td>
                <td style="padding:1px 3px;font-size:7.5pt">${plan.class || ''}${plan.ageRange ? ' (' + plan.ageRange + ')' : ''}</td>
            </tr>
            <tr>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700">Term:</td>
                <td style="padding:1px 3px;font-size:7.5pt">${plan.term || ''}</td>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700">Week:</td>
                <td style="padding:1px 3px;font-size:7.5pt">${plan.week || ''}</td>
            </tr>
            <tr>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700">Date:</td>
                <td style="padding:1px 3px;font-size:7.5pt">${plan.date || ''}</td>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700">Duration:</td>
                <td style="padding:1px 3px;font-size:7.5pt">${plan.duration || ''}</td>
            </tr>
            <tr>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700" colspan="2">Topic:</td>
                <td style="padding:1px 3px;font-size:7.5pt" colspan="2">${plan.topic || ''}</td>
            </tr>
            <tr>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700" colspan="4">Behavioural Objectives</td>
            </tr>
            ${objItems}
            ${materials.length ? `<tr><td style="padding:1px 3px;font-size:7pt;font-weight:700" colspan="4">Instructional Materials</td></tr>${matItems}` : ''}
            ${plan.previousKnowledge ? `<tr><td style="padding:1px 3px;font-size:7pt;font-weight:700" colspan="4">Previous Knowledge</td></tr><tr><td style="padding:1px 3px;font-size:7pt" colspan="4">${plan.previousKnowledge}</td></tr>` : ''}
            <tr>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700" colspan="4">Lesson Procedure</td>
            </tr>
            <tr>
                <td colspan="4" style="padding:0;border:0">
                    <table style="width:100%;border-collapse:collapse;font-size:7pt">
                        ${stepsHeader}
                        ${stepsHtml || '<tr><td style="padding:2px;border:1px solid #000;font-size:7pt" colspan="4">No steps available</td></tr>'}
                    </table>
                </td>
            </tr>
            ${plan.evaluation ? `<tr><td style="padding:1px 3px;font-size:7pt;font-weight:700" colspan="4">Evaluation</td></tr><tr><td style="padding:1px 3px;font-size:7pt" colspan="4">${plan.evaluation}</td></tr>` : ''}
            ${plan.assignment ? `<tr><td style="padding:1px 3px;font-size:7pt;font-weight:700" colspan="4">Assignment / Homework</td></tr><tr><td style="padding:1px 3px;font-size:7pt" colspan="4">${plan.assignment}</td></tr>` : ''}
            ${plan.summary ? `<tr><td style="padding:1px 3px;font-size:7pt;font-weight:700" colspan="4">Summary</td></tr><tr><td style="padding:1px 3px;font-size:7pt" colspan="4">${plan.summary}</td></tr>` : ''}
            ${plan.conclusion ? `<tr><td style="padding:1px 3px;font-size:7pt;font-weight:700" colspan="4">Conclusion</td></tr><tr><td style="padding:1px 3px;font-size:7pt" colspan="4">${plan.conclusion}</td></tr>` : ''}
            ${remarksHtml}
            ${sigHtml}
        </table>
    </div>
    `;

    document.getElementById('plan-action-buttons').innerHTML = `
        <button onclick="downloadPlan('pdf')" class="px-3 py-1.5 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700 cursor-pointer no-print">PDF</button>
        <button onclick="downloadPlan('docx')" class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-lg hover:bg-blue-700 cursor-pointer no-print">DOCX</button>
        <button onclick="printPlan()" class="px-3 py-1.5 bg-slate-600 text-white text-xs font-bold rounded-lg hover:bg-slate-700 cursor-pointer no-print">Print</button>
        <button onclick="copyPlanContent()" class="px-3 py-1.5 bg-amber-600 text-white text-xs font-bold rounded-lg hover:bg-amber-700 cursor-pointer no-print">Copy</button>
        <button onclick="sharePlan()" class="px-3 py-1.5 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700 cursor-pointer no-print">Share</button>
        <button onclick="readAloud('plan-content')" class="px-3 py-1.5 bg-purple-600 text-white text-xs font-bold rounded-lg hover:bg-purple-700 cursor-pointer no-print">Read Aloud</button>
        <button onclick="deletePlan()" class="px-3 py-1.5 bg-red-700 text-white text-xs font-bold rounded-lg hover:bg-red-800 cursor-pointer no-print">Delete</button>
    `;
}

function deletePlan() {
    if (!currentPlanId || !confirm('Delete this lesson plan? This cannot be undone.')) return;
    fetch('/api/lesson-plans/' + currentPlanId, { method: 'DELETE', headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                currentPlanId = null;
                document.getElementById('plan-preview').classList.add('hidden');
                loadTeacherData();
            } else { alert('Delete failed.'); }
        })
        .catch(() => alert('Network error.'));
}

function downloadPlan(format) { if (currentPlanId) window.open('/api/download/lesson-plan/' + currentPlanId + '/' + format, '_blank'); }
function printPlan() { window.print(); }
function copyPlanContent() {
    const text = document.getElementById('plan-content').innerText;
    navigator.clipboard.writeText(text).then(() => alert('Lesson plan copied!')).catch(() => alert('Failed to copy.'));
}
function sharePlan() {
    const text = document.getElementById('plan-content').innerText;
    if (navigator.share) navigator.share({ title: 'Lesson Plan', text }).catch(() => {});
    else { copyPlanContent(); alert('Content copied for sharing!'); }
}

// ====== LESSON NOTE ======
document.getElementById('lesson-note-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('note-submit-btn');
    btn.disabled = true; btn.textContent = 'Generating...';
    try {
        const res = await fetch('/api/ai/lesson-note', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                subject: document.getElementById('note-subject').value,
                class: document.getElementById('note-class').value,
                term: document.getElementById('note-term').value,
                week: parseInt(document.getElementById('note-week').value),
                topic: document.getElementById('note-topic').value,
                periods: document.getElementById('note-periods').value,
                difficulty: document.getElementById('note-difficulty').value,
            })
        });
        const data = await res.json();
        if (data.success) {
            currentNoteId = data.noteId;
            displayLessonNote(data.note);
            loadTeacherData();
        } else { alert(data.error || 'Generation failed.'); }
    } catch(e) { alert('Network error.'); }
    finally { btn.disabled = false; btn.textContent = 'Generate Lesson Note'; }
});

function displayLessonNote(note) {
    document.getElementById('note-preview').classList.remove('hidden');
    const container = document.getElementById('note-content');
    const examples = note.examples || [];
    const activities = note.classroomActivities || [];
    const evaluation = note.evaluationQuestions || [];

    let examplesHtml = examples.map(ex => `<div class="p-3 bg-slate-50 border-l-4 border-emerald-500 rounded mb-2"><strong class="text-sm">${ex.title || 'Example'}:</strong><p class="text-xs mt-1">${ex.description || ''}</p></div>`).join('');

    container.innerHTML = `
        <div class="text-center border-b-2 border-emerald-600 pb-3 mb-4">
            <h1 class="text-xl font-bold text-emerald-700">${note.topic || ''}</h1>
            <p class="text-xs text-slate-500">${note.subject || ''} | ${note.class || ''} | ${note.term || ''} | Week ${note.week || ''} | ${note.periods || ''}</p>
        </div>
        ${note.content || ''}
        ${examplesHtml ? `<h3 class="text-base font-bold text-slate-800 mt-4 mb-2">Examples</h3>${examplesHtml}` : ''}
        ${activities.length ? `<h3 class="text-base font-bold text-slate-800 mt-4 mb-2">Classroom Activities</h3>${activities.map(a => `<div class="mb-2"><strong class="text-sm">${a.title}:</strong><p class="text-xs mt-1">${a.description}</p></div>`).join('')}` : ''}
        ${evaluation.length ? `<h3 class="text-base font-bold text-slate-800 mt-4 mb-2">Evaluation Questions</h3><ol class="text-sm pl-4 space-y-1">${evaluation.map(eq => `<li>${eq}</li>`).join('')}</ol>` : ''}
        ${note.summary ? `<h3 class="text-base font-bold text-slate-800 mt-4 mb-2">Summary</h3><p class="text-sm">${note.summary}</p>` : ''}
        ${note.assignment ? `<h3 class="text-base font-bold text-slate-800 mt-4 mb-2">Assignment</h3><div class="text-sm whitespace-pre-wrap">${note.assignment}</div>` : ''}
    `;

    document.getElementById('note-action-buttons').innerHTML = `
        <button onclick="downloadNote('pdf')" class="px-3 py-1.5 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700 cursor-pointer">PDF</button>
        <button onclick="downloadNote('docx')" class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-lg hover:bg-blue-700 cursor-pointer">DOCX</button>
        <button onclick="printNote()" class="px-3 py-1.5 bg-slate-600 text-white text-xs font-bold rounded-lg hover:bg-slate-700 cursor-pointer">Print</button>
        <button onclick="copyNoteContent()" class="px-3 py-1.5 bg-amber-600 text-white text-xs font-bold rounded-lg hover:bg-amber-700 cursor-pointer">Copy</button>
        <button onclick="copyNoteLink()" class="px-3 py-1.5 bg-cyan-600 text-white text-xs font-bold rounded-lg hover:bg-cyan-700 cursor-pointer" title="Copy note link"><svg class="w-3.5 h-3.5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg></button>
        <button onclick="deleteNote()" class="px-3 py-1.5 bg-red-700 text-white text-xs font-bold rounded-lg hover:bg-red-800 cursor-pointer">Delete</button>
        <button onclick="shareNote()" class="px-3 py-1.5 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700 cursor-pointer">Share</button>
        <button onclick="readAloud('note-content')" class="px-3 py-1.5 bg-purple-600 text-white text-xs font-bold rounded-lg hover:bg-purple-700 cursor-pointer">Read Aloud</button>
        <button onclick="generateQuestionsFromNote()" class="px-3 py-1.5 bg-pink-600 text-white text-xs font-bold rounded-lg hover:bg-pink-700 cursor-pointer">Generate Q from Note</button>
    `;
}

function downloadNote(format) { if (currentNoteId) window.open('/api/download/lesson-note/' + currentNoteId + '/' + format, '_blank'); }
function printNote() { window.print(); }
function copyNoteContent() {
    navigator.clipboard.writeText(document.getElementById('note-content').innerText).then(() => alert('Lesson note copied!')).catch(() => {});
}
function shareNote() {
    const text = document.getElementById('note-content').innerText;
    if (navigator.share) navigator.share({ title: 'Lesson Note', text }).catch(() => {});
    else { copyNoteContent(); alert('Content copied for sharing!'); }
}
function copyNoteLink() {
    if (!currentNoteId) return;
    const url = window.location.origin + '/shared/note/' + currentNoteId;
    navigator.clipboard.writeText(url).then(() => alert('Note link copied!')).catch(() => { fallbackCopy(url); });
}
function fallbackCopy(text) {
    const ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); alert('Note link copied!');
}
async function deleteNote() {
    if (!currentNoteId || !confirm('Delete this lesson note? This cannot be undone.')) return;
    try {
        const res = await fetch('/api/lesson-notes/' + currentNoteId, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) {
            currentNoteId = null;
            document.getElementById('note-preview').classList.add('hidden');
            loadTeacherData();
        } else { alert('Delete failed.'); }
    } catch(e) { alert('Network error.'); }
}
async function generateQuestionsFromNote() {
    if (!currentNoteId) return;
    document.getElementById('q-subject').value = document.getElementById('note-subject').value;
    document.getElementById('q-topic').value = document.getElementById('note-topic').value;
    alert('Switched to Question Pool tab. Click Generate to create questions from this note.');
    switchTab('questions');
}

// ====== READ ALOUD ======
let speechSynth = window.speechSynthesis;
let speechUtterance = null;

function readAloud(elementId) {
    if (speechUtterance && speechSynthesis.speaking) {
        if (speechSynthesis.paused) { speechSynthesis.resume(); return; }
        speechSynthesis.cancel(); return;
    }
    const text = document.getElementById(elementId).innerText;
    if (!text) return;
    speechUtterance = new SpeechSynthesisUtterance(text);
    speechUtterance.lang = 'en-GB';
    speechUtterance.rate = 0.9;
    speechUtterance.pitch = 1;
    speechSynthesis.speak(speechUtterance);
}

// ====== QUESTIONS ======
document.getElementById('questions-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('q-submit-btn');
    btn.disabled = true; btn.textContent = 'Generating...';
    const lessonNoteId = currentNoteId || null;
    try {
        const res = await fetch('/api/ai/questions', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                subject: document.getElementById('q-subject').value,
                topic: document.getElementById('q-topic').value,
                class: document.getElementById('q-class').value,
                term: document.getElementById('q-term').value,
                week: parseInt(document.getElementById('q-week').value) || 1,
                count: parseInt(document.getElementById('q-count').value),
                includeTheory: document.getElementById('q-theory').checked,
                lessonNoteId: lessonNoteId,
            })
        });
        const data = await res.json();
        if (data.success) {
            currentQuestions = data.questions;
            displayQuestions(data.questions);
            document.getElementById('q-save-section').classList.remove('hidden');
            document.getElementById('q-save-msg').textContent = data.message;
        } else { alert(data.error || 'Generation failed.'); }
    } catch(e) { alert('Network error.'); }
    finally { btn.disabled = false; btn.textContent = 'Generate Questions'; }
});

function displayQuestions(qs) {
    document.getElementById('q-preview').classList.remove('hidden');
    const container = document.getElementById('q-content');
    const objectives = qs.objectives || [];
    const theory = qs.theoryQuestions || [];
    const essay = qs.essayQuestions || [];
    const structured = qs.structuredQuestions || [];

    let html = '';
    if (objectives.length) {
        html += `<h3 class="text-base font-bold text-slate-800 mb-3">Objective Questions (${objectives.length})</h3>`;
        html += objectives.map(q => `
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg mb-2">
                <p class="text-sm font-medium mb-1">${q.id}. ${q.question}</p>
                <ul class="text-xs text-slate-600 grid grid-cols-2 gap-1 pl-4">
                    <li>A. ${q.A || ''}</li>
                    <li>B. ${q.B || ''}</li>
                    <li>C. ${q.C || ''}</li>
                    <li>D. ${q.D || ''}</li>
                </ul>
                <p class="text-xs text-emerald-700 font-bold mt-1">Answer: ${q.answer || ''}</p>
            </div>
        `).join('');
    }
    if (theory.length) {
        html += `<h3 class="text-base font-bold text-slate-800 mt-4 mb-3">Theory Questions</h3>`;
        html += theory.map(q => `<div class="p-3 bg-amber-50 border border-amber-200 rounded-lg mb-2"><p class="text-sm font-medium">${q.question}</p><p class="text-xs text-slate-500 mt-1">Model Answer: ${q.answer || ''}</p></div>`).join('');
    }
    if (essay.length) {
        html += `<h3 class="text-base font-bold text-slate-800 mt-4 mb-3">Essay Questions</h3>`;
        html += essay.map(q => `<div class="p-3 bg-purple-50 border border-purple-200 rounded-lg mb-2"><p class="text-sm font-medium">${q.question}</p><p class="text-xs text-slate-500 mt-1">Guidance: ${q.guidance || ''}</p></div>`).join('');
    }
    if (structured.length) {
        html += `<h3 class="text-base font-bold text-slate-800 mt-4 mb-3">Structured Questions</h3>`;
        html += structured.map(q => `<div class="p-3 bg-blue-50 border border-blue-200 rounded-lg mb-2"><p class="text-sm font-medium">${q.question}</p>${q.parts ? Object.entries(q.parts).map(([k,v]) => `<p class="text-xs text-slate-600 ml-2">(${k}) ${v}</p>`).join('') : ''}</div>`).join('');
    }
    container.innerHTML = html || '<p class="text-sm text-slate-400">No questions generated.</p>';

    document.getElementById('q-action-buttons').innerHTML = `
        <button onclick="copyQuestions()" class="px-3 py-1.5 bg-amber-600 text-white text-xs font-bold rounded-lg hover:bg-amber-700 cursor-pointer">Copy</button>
        <button onclick="shareQuestions()" class="px-3 py-1.5 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700 cursor-pointer">Share</button>
        <button onclick="printQuestions()" class="px-3 py-1.5 bg-slate-600 text-white text-xs font-bold rounded-lg hover:bg-slate-700 cursor-pointer">Print</button>
        <button onclick="readAloud('q-content')" class="px-3 py-1.5 bg-purple-600 text-white text-xs font-bold rounded-lg hover:bg-purple-700 cursor-pointer">Read Aloud</button>
    `;
}

async function saveQuestions() {
    if (!currentQuestions) return;
    try {
        const res = await fetch('/api/questions/save', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                subject: document.getElementById('q-subject').value,
                topic: document.getElementById('q-topic').value,
                questions: currentQuestions.objectives || [],
            })
        });
        const data = await res.json();
        if (data.success) {
            currentQsId = data.questionSetId;
            document.getElementById('q-save-msg').textContent = 'Questions saved! You can now convert to CBT.';
            loadTeacherData();
        } else { alert(data.error || 'Save failed.'); }
    } catch(e) { alert('Network error.'); }
}

async function convertToCBT() {
    let qsId = currentQsId;
    if (!qsId && currentQuestions) {
        await saveQuestions();
        qsId = currentQsId;
    }
    if (!qsId) { alert('Please save questions first.'); return; }
    try {
        const res = await fetch('/api/questions/convert-to-exam', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                questionSetId: qsId,
                title: document.getElementById('q-subject').value + ' CBT Exam',
                duration: Math.max(10, Math.min(60, Math.floor((currentQuestions?.objectives?.length || 20) / 2))),
            })
        });
        const data = await res.json();
        if (data.success) {
            alert('Exam created! ' + data.message);
            loadTeacherData();
            switchTab('cbt-engine');
        } else { alert(data.error || 'Conversion failed.'); }
    } catch(e) { alert('Network error.'); }
}

function copyQuestions() {
    navigator.clipboard.writeText(document.getElementById('q-content').innerText).then(() => alert('Questions copied!')).catch(() => {});
}
function shareQuestions() {
    const text = document.getElementById('q-content').innerText;
    if (navigator.share) navigator.share({ title: 'Questions', text }).catch(() => {});
    else { copyQuestions(); alert('Content copied for sharing!'); }
}
function printQuestions() { window.print(); }

// ====== RENDER SAVED ITEMS ======
function renderPlans() {
    const container = document.getElementById('plans-list');
    if (!teacherData.plans.length) {
        container.innerHTML = '<div class="text-center py-4 text-sm text-slate-400">No lesson plans yet.</div>';
        return;
    }
    container.innerHTML = teacherData.plans.slice().reverse().map(p => `
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg cursor-pointer hover:border-indigo-300 transition" onclick="viewPlan('${p.id}')">
            <div class="font-medium text-sm text-slate-900">${p.topic || 'Lesson Plan'}</div>
            <div class="text-xs text-slate-400">${p.subject || ''} | ${p.class || ''} | Week ${p.week || ''} | ${p.createdAt ? new Date(p.createdAt).toLocaleDateString() : ''}</div>
        </div>
    `).join('');
}

function viewPlan(id) {
    const plan = teacherData.plans.find(p => p.id === id);
    if (plan) { currentPlanId = id; displayLessonPlan(plan); }
}

function renderNotes() {
    const container = document.getElementById('notes-list');
    if (!teacherData.notes.length) {
        container.innerHTML = '<div class="text-center py-4 text-sm text-slate-400">No lesson notes yet.</div>';
        return;
    }
    container.innerHTML = teacherData.notes.slice().reverse().map(n => `
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg cursor-pointer hover:border-emerald-300 transition" onclick="viewNote('${n.id}')">
            <div class="font-medium text-sm text-slate-900">${n.topic || 'Lesson Note'}</div>
            <div class="text-xs text-slate-400">${n.subject || ''} | ${n.class || ''} | ${n.createdAt ? new Date(n.createdAt).toLocaleDateString() : ''}</div>
        </div>
    `).join('');
}

function viewNote(id) {
    const note = teacherData.notes.find(n => n.id === id);
    if (note) { currentNoteId = id; displayLessonNote(note); }
}

function renderExams() {
    const container = document.getElementById('cbt-exams-list');
    if (!teacherData.exams.length) {
        container.innerHTML = '<div class="text-center py-8 text-sm text-slate-400 col-span-2">No exams created yet.</div>';
        return;
    }
    container.innerHTML = teacherData.exams.slice().reverse().map(e => {
        const examLink = window.location.origin + '/student/exam/' + e.id;
        return `<div class="p-4 bg-white border border-slate-200 rounded-xl">
            <div class="flex justify-between items-start">
                <div>
                    <h5 class="font-semibold text-slate-900">${e.title || 'Exam'}</h5>
                    <p class="text-xs text-slate-500">${e.subject || ''} | ${e.questions?.length || 0} questions | ${e.duration || 0} min</p>
                </div>
                <span class="px-2 py-0.5 rounded text-xs font-semibold ${e.isPublished ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}">${e.isPublished ? 'Live' : 'Draft'}</span>
            </div>
            <div class="mt-3 flex flex-wrap gap-1.5">
                <button onclick="publishExam('${e.id}')" class="px-2 py-1 bg-emerald-600 text-white text-[10px] font-bold rounded hover:bg-emerald-700 cursor-pointer">${e.isPublished ? 'Unpublish' : 'Publish'}</button>
                <button onclick="copyExamLink('${examLink}')" class="px-2 py-1 bg-indigo-600 text-white text-[10px] font-bold rounded hover:bg-indigo-700 cursor-pointer">Copy Link</button>
                <button onclick="window.open('/api/download/exam/${e.id}/pdf','_blank')" class="px-2 py-1 bg-red-600 text-white text-[10px] font-bold rounded hover:bg-red-700 cursor-pointer">PDF</button>
                <button onclick="window.open('/api/download/exam/${e.id}/docx','_blank')" class="px-2 py-1 bg-blue-600 text-white text-[10px] font-bold rounded hover:bg-blue-700 cursor-pointer">DOCX</button>
                <button onclick="deleteExam('${e.id}')" class="px-2 py-1 bg-red-500 text-white text-[10px] font-bold rounded hover:bg-red-600 cursor-pointer">Delete</button>
            </div>
        </div>`;
    }).join('');
}

async function publishExam(id) {
    try {
        const res = await fetch('/api/exams/' + id + '/publish', { method: 'POST', headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (data.success) loadTeacherData();
    } catch(e) {}
}

function copyExamLink(link) {
    navigator.clipboard.writeText(link).then(() => alert('Exam link copied: ' + link)).catch(() => {});
}

async function deleteExam(id) {
    if (!confirm('Delete this exam?')) return;
    try {
        const res = await fetch('/api/exams/' + id, { method: 'DELETE', headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (data.success) loadTeacherData();
    } catch(e) {}
}

function renderResults() {
    const container = document.getElementById('results-list');
    if (!teacherData.results.length) {
        container.innerHTML = '<div class="text-center py-8 text-sm text-slate-400">No results yet.</div>';
        return;
    }
    container.innerHTML = teacherData.results.slice().reverse().map(r => `
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
            <div class="flex justify-between items-center">
                <div>
                    <span class="font-medium text-sm text-slate-900">${r.studentName || 'Student'}</span>
                    <span class="text-xs text-slate-400 ml-2">${r.examTitle || ''}</span>
                </div>
                <span class="px-2 py-0.5 rounded text-xs font-bold ${(r.percentage || 0) >= 50 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'}">${r.percentage || 0}%</span>
            </div>
            <div class="text-xs text-slate-400 mt-1">Score: ${r.score || 0}/${r.totalQuestions || 0} | ${r.date ? new Date(r.date).toLocaleDateString() : ''}</div>
        </div>
    `).join('');
}

// ====== TAB SWITCHING ======
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        const isActive = btn.id === 'tab-' + tab + '-btn';
        btn.className = 'tab-btn px-4 py-3 text-sm font-semibold border-b-2 transition cursor-pointer whitespace-nowrap ' + (isActive ? 'border-indigo-600 text-indigo-700 bg-white' : 'border-transparent text-slate-500 hover:text-slate-700');
    });
    document.querySelectorAll('.tab-panel').forEach(p => {
        p.classList.toggle('hidden', p.id !== 'tab-' + tab);
    });
}

// ====== INIT ======
loadCurriculumSelects();
loadTeacherData();
</script>
@endsection
