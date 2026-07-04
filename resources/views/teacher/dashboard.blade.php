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
                            <div class="flex items-center space-x-2 overflow-x-auto pb-2 mb-3" id="plans-subjects-scroll"></div>
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
                            <div class="flex items-center space-x-2 overflow-x-auto pb-2 mb-3" id="notes-subjects-scroll"></div>
                            <h4 class="text-sm font-bold text-slate-800 mb-3">Saved Lesson Notes</h4>
                            <div id="notes-list" class="space-y-2 max-h-[500px] overflow-y-auto"></div>
                        </div>
                    </div>

                    <div id="note-preview" class="hidden mt-6 bg-white border border-slate-200 rounded-xl p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-4 border-b border-slate-200 pb-4">
                            <h3 class="text-lg font-bold text-slate-900">Lesson Note Preview</h3>
                            <div class="flex flex-wrap gap-2" id="note-action-buttons"></div>
                        </div>
                        <div id="note-content" class="prose max-w-none text-sm"></div>
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
                            <div class="flex items-center space-x-2 overflow-x-auto pb-2 mb-3" id="qs-subjects-scroll"></div>
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
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-bold text-slate-900">CBT Exam Manager</h3>
                            <button onclick="openCsvImport()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition cursor-pointer flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Import Questions (CSV)
                            </button>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                            <button onclick="this.nextElementSibling.classList.toggle('hidden');this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between p-4 text-xs font-bold text-slate-500 hover:text-slate-700 transition cursor-pointer border-b border-slate-100 bg-slate-50">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    CSV Format Reference
                                </span>
                                <svg class="w-4 h-4 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div class="hidden p-4 text-xs space-y-3">
                                <p class="text-slate-600 font-medium">Your CSV file must have these columns (header row required):</p>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs border-collapse">
                                        <thead>
                                            <tr class="bg-slate-100 text-slate-700 font-bold">
                                                <th class="p-2 border border-slate-200 text-left">Question</th>
                                                <th class="p-2 border border-slate-200 text-left">Option A</th>
                                                <th class="p-2 border border-slate-200 text-left">Option B</th>
                                                <th class="p-2 border border-slate-200 text-left">Option C</th>
                                                <th class="p-2 border border-slate-200 text-left">Option D</th>
                                                <th class="p-2 border border-slate-200 text-left">Correct Answer</th>
                                                <th class="p-2 border border-slate-200 text-left">Explanation</th>
                                                <th class="p-2 border border-slate-200 text-left">Marks</th>
                                                <th class="p-2 border border-slate-200 text-left">Difficulty</th>
                                                <th class="p-2 border border-slate-200 text-left">Topic</th>
                                                <th class="p-2 border border-slate-200 text-left">Image URL</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="bg-white text-slate-600">
                                                <td class="p-2 border border-slate-200 font-medium text-slate-800">What is the capital of Nigeria?</td>
                                                <td class="p-2 border border-slate-200">Lagos</td>
                                                <td class="p-2 border border-slate-200">Abuja</td>
                                                <td class="p-2 border border-slate-200">Kano</td>
                                                <td class="p-2 border border-slate-200">Ibadan</td>
                                                <td class="p-2 border border-slate-200 font-bold text-emerald-700">B</td>
                                                <td class="p-2 border border-slate-200">Abuja is the capital city of Nigeria.</td>
                                                <td class="p-2 border border-slate-200">1</td>
                                                <td class="p-2 border border-slate-200">Easy</td>
                                                <td class="p-2 border border-slate-200">Geography</td>
                                                <td class="p-2 border border-slate-200 text-slate-400">—</td>
                                            </tr>
                                            <tr class="bg-slate-50 text-slate-600">
                                                <td class="p-2 border border-slate-200 font-medium text-slate-800">Which planet is known as the Red Planet?</td>
                                                <td class="p-2 border border-slate-200">Earth</td>
                                                <td class="p-2 border border-slate-200">Mars</td>
                                                <td class="p-2 border border-slate-200">Venus</td>
                                                <td class="p-2 border border-slate-200">Jupiter</td>
                                                <td class="p-2 border border-slate-200 font-bold text-emerald-700">B</td>
                                                <td class="p-2 border border-slate-200">Mars is called the Red Planet due to its reddish appearance.</td>
                                                <td class="p-2 border border-slate-200">1</td>
                                                <td class="p-2 border border-slate-200">Easy</td>
                                                <td class="p-2 border border-slate-200">Space</td>
                                                <td class="p-2 border border-slate-200 text-slate-400">—</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="flex flex-wrap gap-4 text-slate-500">
                                    <span><strong class="text-slate-700">Correct Answer:</strong> Letter (A, B, C, D)</span>
                                    <span><strong class="text-slate-700">Difficulty:</strong> Easy, Medium, or Hard</span>
                                    <span><strong class="text-slate-700">Explanation, Marks, Topic, Image URL</strong> are optional</span>
                                </div>
                                <div class="flex items-center gap-3 text-slate-500">
                                    <span>Download a <button type="button" onclick="downloadCsvTemplate()" class="text-indigo-600 hover:underline font-medium cursor-pointer">CSV template</button> to get started.</span>
                                    <button onclick="navigator.clipboard.writeText('Question,Option A,Option B,Option C,Option D,Correct Answer,Explanation,Marks,Difficulty,Topic,Image URL');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy Headers',2000)" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-md font-medium transition cursor-pointer text-xs">
                                        Copy Headers
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5">
                            <div id="cbt-exams-list" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="text-center py-8 text-sm text-slate-400 col-span-2">No exams created yet.</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- === CSV Import Modal === --}}
                <div id="csv-import-modal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-start justify-center pt-10 pb-10 overflow-y-auto">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto">
                        <div class="sticky top-0 bg-white rounded-t-2xl border-b border-slate-200 px-6 py-4 flex items-center justify-between z-10">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Import Questions (CSV)</h3>
                                <p class="text-xs text-slate-500" id="csv-import-step-label">Step 1 of 3: Upload File</p>
                            </div>
                            <button onclick="closeCsvImport()" class="p-2 hover:bg-slate-100 rounded-lg transition cursor-pointer">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Step 1: Upload --}}
                        <div id="csv-step-1" class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-600 block mb-1">Subject *</label>
                                    <select id="csv-subject" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-emerald-500">
                                        <option value="">Select subject...</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600 block mb-1">Class *</label>
                                    <select id="csv-class" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-emerald-500"></select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-600 block mb-1">Term *</label>
                                    <select id="csv-term" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-emerald-500"></select>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600 block mb-1">Session *</label>
                                    <input type="text" id="csv-session" value="<?php echo date('Y') . '/' . (date('Y') + 1); ?>" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-emerald-500">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-600 block mb-1">Exam Type *</label>
                                    <select id="csv-exam-type" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                                        <option value="CBT">CBT (Multiple Choice)</option>
                                        <option value="Mixed">Mixed (Objective & Theory)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600 block mb-1">Topic (Optional)</label>
                                    <input type="text" id="csv-topic" placeholder="e.g., Algebra" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-emerald-500">
                                </div>
                            </div>
                            <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-emerald-400 transition" id="csv-drop-zone">
                                <input type="file" id="csv-file-input" accept=".csv" class="hidden" onchange="handleCsvFile(this)">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <p class="text-sm text-slate-500 mb-1">Drag & drop your CSV file here, or <button type="button" onclick="document.getElementById('csv-file-input').click()" class="text-emerald-600 font-semibold hover:underline cursor-pointer">browse</button></p>
                                <p class="text-xs text-slate-400">Supports up to 5,000 questions. <button type="button" onclick="downloadCsvTemplate()" class="text-indigo-600 hover:underline cursor-pointer font-medium">Download CSV Template</button></p>
                                <div id="csv-file-info" class="hidden mt-3 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                                    <p class="text-sm text-emerald-800 font-medium" id="csv-file-name"></p>
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button onclick="closeCsvImport()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-lg transition cursor-pointer">Cancel</button>
                                <button id="csv-preview-btn" onclick="previewCsvImport()" disabled class="px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-lg opacity-50 cursor-not-allowed transition">Preview Import</button>
                            </div>
                        </div>

                        {{-- Step 2: Preview --}}
                        <div id="csv-step-2" class="hidden p-6 space-y-4">
                            <div id="csv-preview-stats" class="grid grid-cols-4 gap-3"></div>
                            <div id="csv-preview-errors" class="hidden p-4 bg-red-50 border border-red-200 rounded-lg">
                                <h4 class="text-sm font-bold text-red-800 mb-2" id="csv-error-title">Errors Found</h4>
                                <div id="csv-error-list" class="text-xs text-red-700 space-y-1 max-h-40 overflow-y-auto"></div>
                            </div>
                            <div class="border border-slate-200 rounded-lg overflow-hidden">
                                <div class="max-h-80 overflow-y-auto">
                                    <table class="w-full text-xs">
                                        <thead class="bg-slate-50 sticky top-0">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-600">#</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-600">Question</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-600">A</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-600">B</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-600">C</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-600">D</th>
                                                <th class="px-3 py-2 text-center font-semibold text-slate-600">Answer</th>
                                                <th class="px-3 py-2 text-center font-semibold text-slate-600">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="csv-preview-table"></tbody>
                                    </table>
                                </div>
                                <div id="csv-preview-more" class="hidden p-3 text-center text-xs text-slate-400 border-t border-slate-200"></div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600 block mb-2">Duplicate Handling</label>
                                <div class="flex flex-wrap gap-3">
                                    <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:border-slate-300 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 transition">
                                        <input type="radio" name="duplicate_handling" value="import_all" checked class="accent-emerald-600">
                                        <div><span class="text-sm font-medium text-slate-800">Import All</span><p class="text-xs text-slate-500">Import everything including duplicates</p></div>
                                    </label>
                                    <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:border-slate-300 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 transition">
                                        <input type="radio" name="duplicate_handling" value="skip" class="accent-amber-600">
                                        <div><span class="text-sm font-medium text-slate-800">Skip Duplicates</span><p class="text-xs text-slate-500">Skip questions that already exist</p></div>
                                    </label>
                                    <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:border-slate-300 has-[:checked]:border-red-500 has-[:checked]:bg-red-50 transition">
                                        <input type="radio" name="duplicate_handling" value="replace" class="accent-red-600">
                                        <div><span class="text-sm font-medium text-slate-800">Replace Existing</span><p class="text-xs text-slate-500">Update matching questions</p></div>
                                    </label>
                                </div>
                            </div>
                            <div class="flex justify-between gap-2 pt-2">
                                <button onclick="csvGoBack(1)" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-lg transition cursor-pointer">Back</button>
                                <div class="flex gap-2">
                                    <span id="csv-import-progress" class="hidden px-4 py-2 text-sm text-emerald-700 font-semibold"><span class="animate-spin inline-block w-4 h-4 border-2 border-emerald-600 border-t-transparent rounded-full mr-2 align-middle"></span>Importing...</span>
                                    <button id="csv-import-btn" onclick="confirmCsvImport()" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition cursor-pointer">Import Questions</button>
                                </div>
                            </div>
                        </div>

                        {{-- Step 3: Result --}}
                        <div id="csv-step-3" class="hidden p-6 text-center space-y-4">
                            <div class="w-16 h-16 mx-auto bg-emerald-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900" id="csv-result-title">Import Complete!</h3>
                            <p class="text-sm text-slate-500" id="csv-result-message"></p>
                            <div id="csv-result-details" class="grid grid-cols-3 gap-3 max-w-sm mx-auto"></div>
                            <div id="csv-result-errors" class="hidden mt-4 p-4 bg-red-50 border border-red-200 rounded-lg text-left">
                                <h4 class="text-sm font-bold text-red-800 mb-2">Row Errors</h4>
                                <div id="csv-result-error-list" class="text-xs text-red-700 space-y-1 max-h-40 overflow-y-auto"></div>
                            </div>
                            <div class="flex justify-center gap-2 pt-2">
                                <button onclick="closeCsvImport()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-lg transition cursor-pointer">Close</button>
                                <button onclick="csvGoBack(1); closeCsvImport(); openCsvImport();" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition cursor-pointer">Import Another</button>
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

            </div>
        </div>
    </div>
</div>

<script>
let teacherData = { plans: [], notes: [], exams: [], results: [], questionSets: [] };
let currentPlanId = null, currentNoteId = null, currentQsId = null;
let currentQuestions = null;
let plansFilter = '', notesFilter = '', qsFilter = '';

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
        teacherData.questionSets = (statsRes.questionSets || []).filter(q => q.teacherId === userId);
        renderPlans();
        renderNotes();
        renderExams();
        renderResults();
        renderQuestionSets();
        renderPlanFilters();
        renderNoteFilters();
        renderQsFilters();

        // Auto-display the most recent lesson note and switch to its tab
        if (teacherData.notes.length > 0) {
            const last = teacherData.notes[teacherData.notes.length - 1];
            currentNoteId = last.id;
            displayLessonNote(last);
            switchTab('lesson-notes');
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

// ====== SUBJECT FILTERS ======
function renderSubjectFilter(containerId, allItems, currentFilter, setFilter, renderFn) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const subs = [...new Set(allItems.map(i => i.subject).filter(Boolean))];
    const allBtn = `<button onclick="setFilter('');${renderFn}()" class="py-1.5 px-3 rounded-lg text-xs font-bold whitespace-nowrap transition cursor-pointer border ${!currentFilter ? 'bg-indigo-600 text-white border-indigo-700' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'}">All</button>`;
    const btns = subs.map(s => `<button onclick="setFilter('${s}');${renderFn}()" class="py-1.5 px-3 rounded-lg text-xs font-bold whitespace-nowrap transition cursor-pointer border ${currentFilter === s ? 'bg-indigo-600 text-white border-indigo-700' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'}">${s}</button>`);
    container.innerHTML = allBtn + btns.join('');
}

function setPlansFilter(s) { plansFilter = s; }
function setNotesFilter(s) { notesFilter = s; }
function setQsFilter(s) { qsFilter = s; }

function renderPlanFilters() { renderSubjectFilter('plans-subjects-scroll', teacherData.plans, plansFilter, 'setPlansFilter', 'renderPlans'); }
function renderNoteFilters() { renderSubjectFilter('notes-subjects-scroll', teacherData.notes, notesFilter, 'setNotesFilter', 'renderNotes'); }
function renderQsFilters() { renderSubjectFilter('qs-subjects-scroll', teacherData.questionSets, qsFilter, 'setQsFilter', 'renderQuestionSets'); }

// ====== RENDER SAVED ITEMS ======
function renderPlans() {
    const container = document.getElementById('plans-list');
    const filtered = plansFilter ? teacherData.plans.filter(p => p.subject === plansFilter) : teacherData.plans;
    if (!filtered.length) {
        container.innerHTML = '<div class="text-center py-4 text-sm text-slate-400">No lesson plans yet.</div>';
        return;
    }
    container.innerHTML = filtered.slice().reverse().map(p => {
        const objs = p.behaviouralObjectives || [];
        const subHtml = objs.length ? `<div class="flex flex-wrap gap-1 mt-1.5">${objs.slice(0, 3).map(o => `<span class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full border border-indigo-100">${o.replace(/^By the end of the lesson, students should be able to /i, '').replace(/^Students will /i, '').substring(0, 40)}</span>`).join('')}${objs.length > 3 ? `<span class="text-[10px] text-slate-400">+${objs.length - 3} more</span>` : ''}</div>` : '';
        return `
        <div class="flex items-start gap-2 p-3 bg-slate-50 border border-slate-200 rounded-lg cursor-pointer hover:border-indigo-300 transition" onclick="viewPlan('${p.id}')">
            <div class="flex-1 min-w-0">
                <div class="font-medium text-sm text-slate-900">${p.topic || 'Lesson Plan'}</div>
                <div class="text-xs text-slate-400">${p.subject || ''} | ${p.class || ''} | Week ${p.week || ''} | ${p.createdAt ? new Date(p.createdAt).toLocaleDateString() : ''}</div>
                ${subHtml}
            </div>
            <button onclick="event.stopPropagation();deletePlan('${p.id}')" class="shrink-0 p-1.5 bg-white border border-slate-200 rounded-lg hover:bg-red-50 hover:border-red-300 hover:text-red-600 text-slate-400 transition cursor-pointer" title="Delete">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>`;
    }).join('');
}

function deletePlan(id) {
    if (!confirm('Delete this lesson plan?')) return;
    fetch('/api/lesson-plans/' + id, { method: 'DELETE' }).then(r => r.json()).then(d => {
        if (d.success) { teacherData.plans = teacherData.plans.filter(p => p.id !== id); renderPlans(); }
    }).catch(() => alert('Failed to delete.'));
}

function viewPlan(id) {
    const plan = teacherData.plans.find(p => p.id === id);
    if (plan) { currentPlanId = id; displayLessonPlan(plan); }
}

function renderNotes() {
    const container = document.getElementById('notes-list');
    const filtered = notesFilter ? teacherData.notes.filter(n => n.subject === notesFilter) : teacherData.notes;
    if (!filtered.length) {
        container.innerHTML = '<div class="text-center py-4 text-sm text-slate-400">No lesson notes yet.</div>';
        return;
    }
    container.innerHTML = filtered.slice().reverse().map(n => {
        const subs = n.subtopics || [];
        const subHtml = subs.length ? `<div class="flex flex-wrap gap-1 mt-1.5">${subs.slice(0, 3).map(s => `<span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full border border-slate-150">${s}</span>`).join('')}${subs.length > 3 ? `<span class="text-[10px] text-slate-400">+${subs.length - 3} more</span>` : ''}</div>` : '';
        return `
        <div class="flex items-start gap-2 p-3 bg-slate-50 border border-slate-200 rounded-lg cursor-pointer hover:border-emerald-300 transition" onclick="viewNote('${n.id}')">
            <div class="flex-1 min-w-0">
                <div class="font-medium text-sm text-slate-900">${n.topic || 'Lesson Note'}</div>
                <div class="text-xs text-slate-400">${n.subject || ''} | ${n.class || ''} | ${n.createdAt ? new Date(n.createdAt).toLocaleDateString() : ''}</div>
                ${subHtml}
            </div>
            <button onclick="event.stopPropagation();deleteNote('${n.id}')" class="shrink-0 p-1.5 bg-white border border-slate-200 rounded-lg hover:bg-red-50 hover:border-red-300 hover:text-red-600 text-slate-400 transition cursor-pointer" title="Delete">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>`;
    }).join('');
}

function deleteNote(id) {
    if (!confirm('Delete this lesson note?')) return;
    fetch('/api/lesson-notes/' + id, { method: 'DELETE' }).then(r => r.json()).then(d => {
        if (d.success) { teacherData.notes = teacherData.notes.filter(n => n.id !== id); renderNotes(); }
    }).catch(() => alert('Failed to delete.'));
}

function viewNote(id) {
    const note = teacherData.notes.find(n => n.id === id);
    if (note) { currentNoteId = id; displayLessonNote(note); }
}

function renderQuestionSets() {
    const container = document.getElementById('q-sets-list');
    const filtered = qsFilter ? teacherData.questionSets.filter(q => q.subject === qsFilter) : teacherData.questionSets;
    if (!filtered.length) {
        container.innerHTML = '<div class="text-center py-4 text-sm text-slate-400">No question sets saved yet.</div>';
        return;
    }
    container.innerHTML = filtered.slice().reverse().map(q => `
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg cursor-pointer hover:border-pink-300 transition" onclick="viewQuestionSet('${q.id}')">
            <div class="font-medium text-sm text-slate-900">${q.topic || 'Question Set'}</div>
            <div class="text-xs text-slate-400">${q.subject || ''} | ${(q.questions || []).length} questions | ${q.createdAt ? new Date(q.createdAt).toLocaleDateString() : ''}</div>
        </div>
    `).join('');
}

function viewQuestionSet(id) {
    const qs = teacherData.questionSets.find(q => q.id === id);
    if (!qs) return;
    currentQsId = id;
    const data = qs.questions || [];
    if (Array.isArray(data) && data.length && !data.objectives) {
        displayQuestions({ objectives: data });
    } else {
        displayQuestions(data);
    }
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

// ====== CSV IMPORT ======
let csvFile = null;
let csvPreviewData = null;

function openCsvImport() {
    document.getElementById('csv-import-modal').classList.remove('hidden');
    document.getElementById('csv-import-step-label').textContent = 'Step 1 of 3: Upload File';
    document.getElementById('csv-step-1').classList.remove('hidden');
    document.getElementById('csv-step-2').classList.add('hidden');
    document.getElementById('csv-step-3').classList.add('hidden');
    csvFile = null;
    csvPreviewData = null;
    document.getElementById('csv-file-input').value = '';
    document.getElementById('csv-file-info').classList.add('hidden');
    document.getElementById('csv-preview-btn').disabled = true;
    document.getElementById('csv-preview-btn').className = 'px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-lg opacity-50 cursor-not-allowed transition';
    document.body.style.overflow = 'hidden';
    // Populate selects from existing curriculum data
    const subjects = document.querySelectorAll('#plan-subject option');
    const subjectSelect = document.getElementById('csv-subject');
    if (subjectSelect.options.length <= 1) {
        [...subjects].forEach(opt => {
            if (opt.value) subjectSelect.add(new Option(opt.value, opt.value));
        });
    }
    ['csv-class', 'csv-term'].forEach(id => {
        const src = document.getElementById(id === 'csv-class' ? 'plan-class' : 'plan-term');
        const tgt = document.getElementById(id);
        if (src && tgt && tgt.options.length <= 1) {
            [...src.options].forEach(opt => {
                if (opt.value) tgt.add(new Option(opt.value, opt.value));
            });
        }
    });
}

function closeCsvImport() {
    document.getElementById('csv-import-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

function handleCsvFile(input) {
    const file = input.files[0];
    if (!file) return;
    if (!file.name.toLowerCase().endsWith('.csv')) {
        alert('Please select a CSV file.');
        input.value = '';
        return;
    }
    csvFile = file;
    document.getElementById('csv-file-name').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    document.getElementById('csv-file-info').classList.remove('hidden');
    document.getElementById('csv-preview-btn').disabled = false;
    document.getElementById('csv-preview-btn').className = 'px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg cursor-pointer transition';
}

async function previewCsvImport() {
    const subject = document.getElementById('csv-subject').value;
    const cls = document.getElementById('csv-class').value;
    const term = document.getElementById('csv-term').value;
    const session = document.getElementById('csv-session').value;
    const examType = document.getElementById('csv-exam-type').value;
    const topic = document.getElementById('csv-topic').value;

    if (!subject || !cls || !term || !session) {
        alert('Please fill in all required fields (Subject, Class, Term, Session).');
        return;
    }
    if (!csvFile) {
        alert('Please select a CSV file.');
        return;
    }

    const btn = document.getElementById('csv-preview-btn');
    btn.disabled = true; btn.textContent = 'Processing...';

    const formData = new FormData();
    formData.append('file', csvFile);
    formData.append('subject', subject);
    formData.append('class', cls);
    formData.append('term', term);
    formData.append('session', session);
    formData.append('exam_type', examType);
    formData.append('topic', topic);

    try {
        const res = await fetch('/api/csv-import/preview', { method: 'POST', body: formData });
        const data = await res.json();
        if (!data.success) {
            alert(data.error || 'Preview failed.');
            btn.disabled = false; btn.textContent = 'Preview Import';
            return;
        }
        csvPreviewData = data;
        showCsvPreview(data);
    } catch (e) {
        alert('Network error while processing file.');
        btn.disabled = false; btn.textContent = 'Preview Import';
    }
}

function showCsvPreview(data) {
    document.getElementById('csv-step-1').classList.add('hidden');
    document.getElementById('csv-step-2').classList.remove('hidden');
    document.getElementById('csv-import-step-label').textContent = 'Step 2 of 3: Review & Confirm';

    document.getElementById('csv-preview-stats').innerHTML = `
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <div class="text-xl font-bold text-slate-900">${data.total_rows}</div>
            <div class="text-xs text-slate-500">Total Rows</div>
        </div>
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-center">
            <div class="text-xl font-bold text-emerald-700">${data.valid_rows}</div>
            <div class="text-xs text-emerald-600">Valid</div>
        </div>
        <div class="p-3 ${data.error_rows > 0 ? 'bg-red-50 border-red-200' : 'bg-slate-50 border-slate-200'} border rounded-lg text-center">
            <div class="text-xl font-bold ${data.error_rows > 0 ? 'text-red-700' : 'text-slate-500'}">${data.error_rows}</div>
            <div class="text-xs ${data.error_rows > 0 ? 'text-red-600' : 'text-slate-500'}">Errors</div>
        </div>
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-center">
            <div class="text-xl font-bold text-amber-700">${data.duplicate_count}</div>
            <div class="text-xs text-amber-600">Duplicates Found</div>
        </div>
    `;

    if (data.error_rows > 0) {
        document.getElementById('csv-preview-errors').classList.remove('hidden');
        document.getElementById('csv-error-title').textContent = data.error_rows + ' Row(s) with Errors';
        document.getElementById('csv-error-list').innerHTML = Object.entries(data.errors || {}).map(([row, errs]) =>
            `<div class="flex gap-2"><span class="font-medium whitespace-nowrap">Row ${row}:</span><span>${errs.join('; ')}</span></div>`
        ).join('');
    } else {
        document.getElementById('csv-preview-errors').classList.add('hidden');
    }

    const tbody = document.getElementById('csv-preview-table');
    const rows = data.rows || [];
    if (rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="px-3 py-4 text-center text-slate-400">No valid rows to display.</td></tr>';
    } else {
        tbody.innerHTML = rows.map(r => {
            const question = r.question.length > 50 ? r.question.substring(0, 50) + '...' : r.question;
            return `<tr class="${r.valid ? '' : 'bg-red-50'} border-b border-slate-100">
                <td class="px-3 py-2 text-slate-500">${r.row}</td>
                <td class="px-3 py-2 font-medium text-slate-800 max-w-[200px] truncate" title="${r.question.replace(/"/g, '&quot;')}">${question || '-'}</td>
                <td class="px-3 py-2 text-slate-600">${r.optionA || '-'}</td>
                <td class="px-3 py-2 text-slate-600">${r.optionB || '-'}</td>
                <td class="px-3 py-2 text-slate-600">${r.optionC || '-'}</td>
                <td class="px-3 py-2 text-slate-600">${r.optionD || '-'}</td>
                <td class="px-3 py-2 text-center font-bold ${r.valid ? 'text-emerald-700' : 'text-red-500'}">${r.correctAnswer || '-'}</td>
                <td class="px-3 py-2 text-center">${r.valid
                    ? '<span class="text-emerald-600 text-xs font-semibold">OK</span>'
                    : '<span class="text-red-600 text-xs font-semibold" title="' + (r.errors || []).join('; ') + '">Error</span>'
                }</td>
            </tr>`;
        }).join('');
    }

    const moreDiv = document.getElementById('csv-preview-more');
    if (data.has_more) {
        moreDiv.classList.remove('hidden');
        moreDiv.textContent = 'Showing first 100 of ' + data.total_all_rows + ' rows.';
    } else {
        moreDiv.classList.add('hidden');
    }

    document.getElementById('csv-import-btn').disabled = data.valid_rows === 0;
    document.getElementById('csv-import-btn').className = data.valid_rows === 0
        ? 'px-6 py-2 bg-slate-300 text-white text-sm font-bold rounded-lg cursor-not-allowed'
        : 'px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg cursor-pointer transition';
}

function csvGoBack(step) {
    document.getElementById('csv-import-step-label').textContent = step === 1 ? 'Step 1 of 3: Upload File' : 'Step 2 of 3: Review & Confirm';
    document.getElementById('csv-step-1').classList.toggle('hidden', step !== 1);
    document.getElementById('csv-step-2').classList.toggle('hidden', step !== 2);
    document.getElementById('csv-step-3').classList.toggle('hidden', step !== 3);
}

async function confirmCsvImport() {
    if (!csvFile || !csvPreviewData || csvPreviewData.valid_rows === 0) return;

    const duplicateHandling = document.querySelector('input[name="duplicate_handling"]:checked')?.value || 'import_all';

    const btn = document.getElementById('csv-import-btn');
    const progress = document.getElementById('csv-import-progress');
    btn.classList.add('hidden');
    progress.classList.remove('hidden');

    const formData = new FormData();
    formData.append('file', csvFile);
    formData.append('subject', document.getElementById('csv-subject').value);
    formData.append('class', document.getElementById('csv-class').value);
    formData.append('term', document.getElementById('csv-term').value);
    formData.append('session', document.getElementById('csv-session').value);
    formData.append('exam_type', document.getElementById('csv-exam-type').value);
    formData.append('topic', document.getElementById('csv-topic').value);
    formData.append('duplicate_handling', duplicateHandling);

    try {
        const res = await fetch('/api/csv-import/import', { method: 'POST', body: formData });
        const data = await res.json();

        progress.classList.add('hidden');
        btn.classList.remove('hidden');

        if (!data.success) {
            alert(data.error || 'Import failed.');
            return;
        }

        showCsvResult(data);
    } catch (e) {
        progress.classList.add('hidden');
        btn.classList.remove('hidden');
        alert('Network error during import.');
    }
}

function showCsvResult(data) {
    document.getElementById('csv-step-2').classList.add('hidden');
    document.getElementById('csv-step-3').classList.remove('hidden');
    document.getElementById('csv-import-step-label').textContent = 'Step 3 of 3: Complete';

    document.getElementById('csv-result-title').textContent = 'Import Complete!';
    document.getElementById('csv-result-message').textContent = data.message;

    document.getElementById('csv-result-details').innerHTML = `
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-center">
            <div class="text-xl font-bold text-emerald-700">${data.imported}</div>
            <div class="text-xs text-emerald-600">Imported</div>
        </div>
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-center">
            <div class="text-xl font-bold text-amber-700">${data.skipped}</div>
            <div class="text-xs text-amber-600">Skipped</div>
        </div>
        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-center">
            <div class="text-xl font-bold text-blue-700">${data.replaced}</div>
            <div class="text-xs text-blue-600">Replaced</div>
        </div>
    `;

    const errDiv = document.getElementById('csv-result-errors');
    const errList = document.getElementById('csv-result-error-list');
    if (data.errors && Object.keys(data.errors).length > 0) {
        errDiv.classList.remove('hidden');
        errList.innerHTML = Object.entries(data.errors).map(([row, errs]) =>
            `<div class="flex gap-2"><span class="font-medium whitespace-nowrap">Row ${row}:</span><span>${errs.join('; ')}</span></div>`
        ).join('');
    } else {
        errDiv.classList.add('hidden');
    }

    loadTeacherData();
}

function downloadCsvTemplate() {
    window.open('/api/csv-import/template', '_blank');
}

// Drop zone support
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('csv-drop-zone');
    if (dropZone) {
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-emerald-500', 'bg-emerald-50');
        });
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-emerald-500', 'bg-emerald-50');
        });
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-emerald-500', 'bg-emerald-50');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('csv-file-input').files = files;
                handleCsvFile(document.getElementById('csv-file-input'));
            }
        });
    }
});

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
