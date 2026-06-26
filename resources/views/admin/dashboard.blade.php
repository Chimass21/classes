@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div id="admin-app" class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-6">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Administrator Console</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900">Admin Dashboard</h1>
                <p class="text-sm text-slate-500">Manage users, monitor activities, and oversee the system.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-bold">Welcome, {{ Session::get('user.name') }}</span>
                <button onclick="fetchData()" class="p-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-slate-600 transition cursor-pointer" title="Refresh">
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

            {{-- Stats Grid --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Users</p>
                            <p class="text-3xl font-bold text-slate-900 mt-1" id="stat-users">0</p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-3 text-xs">
                        <span class="text-emerald-600 font-semibold"><span id="stat-students">0</span> students</span>
                        <span class="text-indigo-600 font-semibold"><span id="stat-teachers">0</span> teachers</span>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Exams</p>
                            <p class="text-3xl font-bold text-slate-900 mt-1" id="stat-exams">0</p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500 font-medium"><span id="stat-published">0</span> published</p>
                </div>
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Lesson Notes</p>
                            <p class="text-3xl font-bold text-slate-900 mt-1" id="stat-notes">0</p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500 font-medium"><span id="stat-plans">0</span> lesson plans</p>
                </div>
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Results</p>
                            <p class="text-3xl font-bold text-slate-900 mt-1" id="stat-results">0</p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500 font-medium">Exam submissions recorded</p>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="border-b border-slate-200">
                    <div class="flex">
                        <button onclick="switchTab('activity')" id="tab-activity-btn" class="tab-btn px-5 py-3 text-sm font-semibold border-b-2 border-indigo-600 text-indigo-700 bg-white transition cursor-pointer">Activity Feed</button>
                        <button onclick="switchTab('users')" id="tab-users-btn" class="tab-btn px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition cursor-pointer">Users</button>
                        <button onclick="switchTab('exams')" id="tab-exams-btn" class="tab-btn px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition cursor-pointer">Exams</button>
                        <button onclick="switchTab('content')" id="tab-content-btn" class="tab-btn px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition cursor-pointer">Content</button>
                    </div>
                </div>

                {{-- Activity Tab --}}
                <div id="tab-activity" class="tab-panel p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-800">Recent Activities</h3>
                        <span class="text-xs text-slate-400" id="activity-count">0 events</span>
                    </div>
                    <div id="activity-list" class="space-y-1 max-h-[500px] overflow-y-auto">
                        <div class="text-center py-8 text-sm text-slate-400">No activities yet.</div>
                    </div>
                </div>

                {{-- Users Tab --}}
                <div id="tab-users" class="tab-panel p-5 hidden">
                    <div class="flex flex-col sm:flex-row gap-3 mb-4">
                        <div class="relative flex-1">
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" id="user-search" oninput="filterUsers()" placeholder="Search by name, email, or ID..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <select id="role-filter" onchange="filterUsers()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none">
                            <option value="all">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 font-semibold text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left">Name / Email</th>
                                    <th class="px-4 py-3 text-left">Role</th>
                                    <th class="px-4 py-3 text-left">ID / Reg No.</th>
                                    <th class="px-4 py-3 text-left">Wallet</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="users-tbody" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>

                {{-- Exams Tab --}}
                <div id="tab-exams" class="tab-panel p-5 hidden">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-800">All Exams</h3>
                        <span class="text-xs text-slate-400" id="exams-count">0 exams</span>
                    </div>
                    <div id="exams-list" class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-[500px] overflow-y-auto"></div>
                </div>

                {{-- Content Tab --}}
                <div id="tab-content" class="tab-panel p-5 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Lesson Notes
                            </h4>
                            <div id="content-notes" class="space-y-2 max-h-[400px] overflow-y-auto"></div>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Lesson Plans
                            </h4>
                            <div id="content-plans" class="space-y-2 max-h-[400px] overflow-y-auto"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div id="edit-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/30 backdrop-blur-sm">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex justify-between items-center">
            <div>
                <h4 class="font-bold text-slate-900">Edit User</h4>
                <p class="text-xs text-slate-400" id="modal-user-email"></p>
            </div>
            <button onclick="closeEditModal()" class="p-1 px-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-500 text-sm cursor-pointer">&times;</button>
        </div>
        <div id="modal-error" class="hidden p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-xs"></div>
        <div class="space-y-3">
            <div>
                <label class="text-xs font-semibold text-slate-600 block mb-1">Current Balance</label>
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg font-bold text-slate-800" id="modal-current-balance"></div>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 block mb-1">Add Wallet Credit (&#8358;)</label>
                <input type="number" id="wallet-amount" value="5000" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
            </div>
        </div>
        <div class="flex gap-2 pt-2">
            <button onclick="handleTopUp()" id="topup-btn" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-lg transition cursor-pointer">Recharge Wallet</button>
            <button onclick="handleToggleRole()" id="toggle-role-btn" class="py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-lg transition cursor-pointer">Toggle Role</button>
        </div>
    </div>
</div>

<script>
let data = { users: [], documents: [], exams: [], results: [], feedback: [], lessonPlans: [], lessonNotes: [] };
let editingUserId = null;

function fetchData() {
    document.getElementById('loading').classList.remove('hidden');
    document.getElementById('content').classList.add('hidden');
    Promise.all([
        fetch('/api/admin/stats').then(r => r.json()),
        fetch('/api/admin/activities').then(r => r.json())
    ]).then(([stats, activityData]) => {
        data = stats;
        data.activities = activityData.activities || [];
        renderStats();
        renderUsers();
        renderActivities();
        renderExams();
        renderContent();
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('content').classList.remove('hidden');
    }).catch(() => {
        document.getElementById('loading').innerHTML = '<p class="text-sm text-red-600 font-medium">Failed to load. <button onclick="fetchData()" class="underline cursor-pointer">Retry</button></p>';
    });
}

function renderStats() {
    document.getElementById('stat-users').textContent = data.users.length;
    document.getElementById('stat-students').textContent = data.users.filter(u => u.role === 'student').length;
    document.getElementById('stat-teachers').textContent = data.users.filter(u => u.role === 'teacher').length;
    document.getElementById('stat-exams').textContent = data.exams.length;
    document.getElementById('stat-published').textContent = data.exams.filter(e => e.isPublished).length;
    document.getElementById('stat-notes').textContent = (data.lessonNotes || []).length;
    document.getElementById('stat-plans').textContent = (data.lessonPlans || []).length;
    document.getElementById('stat-results').textContent = data.results.length;
}

function renderActivities() {
    const container = document.getElementById('activity-list');
    const activities = data.activities || [];
    document.getElementById('activity-count').textContent = activities.length + ' events';
    if (activities.length === 0) {
        container.innerHTML = '<div class="text-center py-8 text-sm text-slate-400">No activities yet.</div>';
        return;
    }
    container.innerHTML = activities.map(a => {
        const ts = a.timestamp ? new Date(a.timestamp).toLocaleString() : '';
        const roleColor = a.userRole === 'admin' ? 'bg-red-100 text-red-700' : a.userRole === 'teacher' ? 'bg-emerald-100 text-emerald-700' : a.userRole === 'student' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600';
        const iconMap = { user: '👤', exam: '📝', note: '📖', plan: '📋', feedback: '💬' };
        return `<div class="flex items-start gap-3 p-3 rounded-lg hover:bg-slate-50 transition">
            <span class="text-lg">${iconMap[a.icon] || '📌'}</span>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-slate-800">${a.title}</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold ${roleColor}">${a.userRole}</span>
                    <span class="text-xs text-slate-400">${ts}</span>
                </div>
            </div>
        </div>`;
    }).join('');
}

function renderUsers() {
    const query = (document.getElementById('user-search').value || '').toLowerCase();
    const roleFilter = document.getElementById('role-filter').value;
    const filtered = data.users.filter(u => {
        const s = (u.name + ' ' + u.email + ' ' + u.id + ' ' + (u.regNumber || '')).toLowerCase();
        if (roleFilter !== 'all' && u.role !== roleFilter) return false;
        return s.includes(query);
    });
    const tbody = document.getElementById('users-tbody');
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-sm text-slate-400">No users found.</td></tr>';
        return;
    }
    tbody.innerHTML = filtered.map(u => {
        const roleBadge = u.role === 'admin' ? 'bg-red-50 text-red-700' : u.role === 'teacher' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700';
        const isSuspended = u.isSuspended;
        return `<tr class="hover:bg-slate-50">
            <td class="px-4 py-3">
                <div class="font-medium text-slate-900">${u.name || 'N/A'}</div>
                <div class="text-xs text-slate-400">${u.email}</div>
            </td>
            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-semibold ${roleBadge}">${u.role}</span></td>
            <td class="px-4 py-3 text-xs text-slate-500 font-mono">${u.regNumber || u.id.substring(0, 12) + '...'}</td>
            <td class="px-4 py-3 text-sm font-semibold text-emerald-700">&#8358;${(u.walletBalance || 0).toLocaleString()}</td>
            <td class="px-4 py-3">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${isSuspended ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'}">
                    <span class="w-1.5 h-1.5 rounded-full ${isSuspended ? 'bg-amber-500' : 'bg-emerald-500'}"></span>
                    ${isSuspended ? 'Suspended' : 'Active'}
                </span>
            </td>
            <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1">
                    <button onclick="openEditModal('${u.id}')" class="p-1.5 hover:bg-slate-100 rounded-lg text-slate-500 cursor-pointer" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button onclick="handleSuspend('${u.id}')" class="p-1.5 hover:bg-slate-100 rounded-lg ${isSuspended ? 'text-emerald-600' : 'text-amber-600'} cursor-pointer" title="${isSuspended ? 'Unsuspend' : 'Suspend'}">
                        ${isSuspended ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>' : '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>'}
                    </button>
                    <button onclick="handleDeleteUser('${u.id}')" class="p-1.5 hover:bg-red-50 rounded-lg text-red-500 cursor-pointer" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function filterUsers() { renderUsers(); }

function renderExams() {
    const container = document.getElementById('exams-list');
    const exams = data.exams || [];
    document.getElementById('exams-count').textContent = exams.length + ' exams';
    if (exams.length === 0) {
        container.innerHTML = '<p class="text-sm text-slate-400 text-center py-8 col-span-2">No exams created yet.</p>';
        return;
    }
    container.innerHTML = exams.map(e => `
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl">
            <div class="flex justify-between items-start">
                <div>
                    <h5 class="font-semibold text-slate-900">${e.title}</h5>
                    <p class="text-xs text-slate-500 mt-0.5">${e.subject || 'N/A'} &middot; ${e.level || 'N/A'}</p>
                </div>
                <span class="px-2 py-0.5 rounded text-xs font-semibold ${e.isPublished ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}">${e.isPublished ? 'Published' : 'Draft'}</span>
            </div>
            <div class="mt-2 flex items-center gap-3 text-xs text-slate-400">
                <span>${e.questions?.length || 0} questions</span>
                <span>${e.duration || 0} min</span>
                <span>By ${e.creatorName || 'Unknown'}</span>
            </div>
        </div>
    `).join('');
}

function renderContent() {
    const notes = data.lessonNotes || [];
    const plans = data.lessonPlans || [];
    const notesContainer = document.getElementById('content-notes');
    const plansContainer = document.getElementById('content-plans');
    if (notes.length === 0) {
        notesContainer.innerHTML = '<p class="text-sm text-slate-400 text-center py-8">No lesson notes yet.</p>';
    } else {
        notesContainer.innerHTML = notes.map(n => `
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                <div class="font-medium text-slate-900">${n.topic || 'Untitled'}</div>
                <div class="text-xs text-slate-400 mt-0.5">${n.subject || 'N/A'} &middot; ${n.classLevel || 'N/A'} &middot; ${n.createdAt ? new Date(n.createdAt).toLocaleDateString() : ''}</div>
            </div>
        `).join('');
    }
    if (plans.length === 0) {
        plansContainer.innerHTML = '<p class="text-sm text-slate-400 text-center py-8">No lesson plans yet.</p>';
    } else {
        plansContainer.innerHTML = plans.map(p => `
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                <div class="font-medium text-slate-900">${p.topic || 'Untitled'}</div>
                <div class="text-xs text-slate-400 mt-0.5">${p.subject || 'N/A'} &middot; ${p.classLevel || 'N/A'} &middot; Week ${p.week || 1}</div>
            </div>
        `).join('');
    }
}

function openEditModal(userId) {
    const user = data.users.find(u => u.id === userId);
    if (!user) return;
    editingUserId = userId;
    document.getElementById('modal-user-email').textContent = user.email;
    document.getElementById('modal-current-balance').textContent = '\u20A6' + (user.walletBalance || 0).toLocaleString();
    document.getElementById('wallet-amount').value = '5000';
    document.getElementById('modal-error').classList.add('hidden');
    document.getElementById('edit-modal').classList.remove('hidden');
    document.getElementById('toggle-role-btn').textContent = 'Make ' + (user.role === 'teacher' ? 'Student' : 'Teacher');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
    editingUserId = null;
}

function handleTopUp() {
    if (!editingUserId) return;
    const user = data.users.find(u => u.id === editingUserId);
    if (!user) return;
    const amount = Number(document.getElementById('wallet-amount').value);
    if (!amount || amount <= 0) {
        document.getElementById('modal-error').textContent = 'Enter a valid amount.';
        document.getElementById('modal-error').classList.remove('hidden');
        return;
    }
    const newBalance = (user.walletBalance || 0) + amount;
    document.getElementById('topup-btn').disabled = true;
    document.getElementById('topup-btn').textContent = 'Processing...';
    fetch('/api/admin/users/' + editingUserId + '/update', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ walletBalance: newBalance })
    }).then(r => r.json()).then(d => {
        if (d.success) { closeEditModal(); fetchData(); }
        else { document.getElementById('modal-error').textContent = d.error || 'Failed.'; document.getElementById('modal-error').classList.remove('hidden'); }
    }).catch(() => {
        document.getElementById('modal-error').textContent = 'Network error.';
        document.getElementById('modal-error').classList.remove('hidden');
    }).finally(() => {
        document.getElementById('topup-btn').disabled = false;
        document.getElementById('topup-btn').textContent = 'Recharge Wallet';
    });
}

function handleToggleRole() {
    if (!editingUserId) return;
    const user = data.users.find(u => u.id === editingUserId);
    if (!user) return;
    const newRole = user.role === 'teacher' ? 'student' : 'teacher';
    fetch('/api/admin/users/' + editingUserId + '/update', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ role: newRole })
    }).then(r => r.json()).then(d => { if (d.success) { closeEditModal(); fetchData(); } });
}

function handleSuspend(userId) {
    const user = data.users.find(u => u.id === userId);
    if (!user) return;
    const action = user.isSuspended ? 'unsuspend' : 'suspend';
    if (!confirm(action + ' ' + (user.name || user.email) + '?')) return;
    fetch('/api/admin/users/' + userId + '/update', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ isSuspended: !user.isSuspended })
    }).then(r => r.json()).then(d => { if (d.success) fetchData(); });
}

function handleDeleteUser(userId) {
    const user = data.users.find(u => u.id === userId);
    if (!confirm('Delete ' + (user?.name || 'this user') + ' permanently?')) return;
    fetch('/api/admin/users/' + userId + '/delete', { method: 'POST' })
        .then(r => r.json()).then(d => { if (d.success) fetchData(); });
}

function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        const isActive = btn.id === 'tab-' + tab + '-btn';
        btn.className = 'tab-btn px-5 py-3 text-sm font-semibold border-b-2 transition cursor-pointer ' + (isActive ? 'border-indigo-600 text-indigo-700 bg-white' : 'border-transparent text-slate-500 hover:text-slate-700');
    });
    document.querySelectorAll('.tab-panel').forEach(p => {
        p.classList.toggle('hidden', p.id !== 'tab-' + tab);
    });
}

fetchData();
</script>
@endsection
