import React, { useState, useEffect } from 'react';
import { 
  Users, BookOpen, GraduationCap, Award, Mail, Ban, ShieldAlert, 
  Sparkles, Check, CheckCircle, Search, Trash2, Edit, Coins, 
  Calendar, CreditCard, RefreshCw, AlertTriangle, ShieldCheck, 
  Trash, MessageSquare, Plus
} from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

interface AdminDashboardProps {
  user: any;
  onLogout: () => void;
}

export default function AdminDashboard({ user, onLogout }: AdminDashboardProps) {
  const [users, setUsers] = useState<any[]>([]);
  const [documents, setDocuments] = useState<any[]>([]);
  const [exams, setExams] = useState<any[]>([]);
  const [results, setResults] = useState<any[]>([]);
  const [feedback, setFeedback] = useState<any[]>([]);
  
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'users' | 'content' | 'feedback'>('users');
  const [searchQuery, setSearchQuery] = useState('');
  const [roleFilter, setRoleFilter] = useState<string>('all');
  
  // Dialog Actions
  const [editingUser, setEditingUser] = useState<any | null>(null);
  const [walletAmount, setWalletAmount] = useState<string>('5000');
  const [submittingAction, setSubmittingAction] = useState(false);
  const [actionSuccess, setActionSuccess] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);

  const fetchAdminStats = async () => {
    setLoading(true);
    try {
      const res = await fetch('/api/admin/stats');
      if (res.ok) {
        const data = await res.json();
        setUsers(data.users || []);
        setDocuments(data.documents || []);
        setExams(data.exams || []);
        setResults(data.results || []);
        setFeedback(data.feedback || []);
      } else {
        console.error('Failed to parse admin stats');
      }
    } catch (err) {
      console.error('Offline stats error:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAdminStats();
  }, []);

  const handleUpdateUser = async (userId: string, updates: any) => {
    setSubmittingAction(true);
    setActionError(null);
    setActionSuccess(null);
    try {
      const res = await fetch(`/api/admin/users/${userId}/update`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updates)
      });
      const data = await res.json();
      if (res.ok && data.success) {
        setActionSuccess(`User account updated successfully!`);
        setEditingUser(null);
        await fetchAdminStats();
      } else {
        setActionError(data.error || 'Failed to update user profile data.');
      }
    } catch {
      setActionError('Network connection failure. Please try again.');
    } finally {
      setSubmittingAction(false);
    }
  };

  const handleDeleteUser = async (userId: string) => {
    if (!window.confirm("Are you absolutely sure you want to permanently delete this user profile? This action is irreversible.")) {
      return;
    }
    try {
      const res = await fetch(`/api/admin/users/${userId}/delete`, {
        method: 'POST'
      });
      const data = await res.json();
      if (res.ok && data.success) {
        alert("User account successfully deleted.");
        await fetchAdminStats();
      } else {
        alert(data.error || "Failed to delete user profile.");
      }
    } catch {
      alert("Network error while deleting profile.");
    }
  };

  const handleDeleteFeedback = async (feedbackId: string) => {
    if (!window.confirm("Delete this support request/feedback inquiry?")) return;
    try {
      const res = await fetch(`/api/admin/feedback/${feedbackId}/delete`, {
        method: 'POST'
      });
      if (res.ok) {
        await fetchAdminStats();
      }
    } catch {
      alert("Network error while deleting feedback.");
    }
  };

  const handleSuspendToggle = async (userObj: any) => {
    const actionLabel = userObj.isSuspended ? "unsuspend" : "suspend";
    if (!window.confirm(`Are you sure you want to ${actionLabel} ${userObj.name || userObj.email}?`)) {
      return;
    }
    await handleUpdateUser(userObj.id, { isSuspended: !userObj.isSuspended });
  };

  const handleRoleChangeSubmit = async (userId: string, newRole: string) => {
    await handleUpdateUser(userId, { role: newRole });
  };

  const handleTopUpSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingUser) return;
    const amount = Number(walletAmount);
    if (isNaN(amount) || amount <= 0) {
      setActionError("Please enter a valid positive credit top-up amount.");
      return;
    }
    const currentBalance = editingUser.walletBalance || 0;
    const newBalance = currentBalance + amount;
    await handleUpdateUser(editingUser.id, { walletBalance: newBalance });
  };

  // Filter logic
  const filteredUsers = users.filter(u => {
    const matchesSearch = 
      (u.name && u.name.toLowerCase().includes(searchQuery.toLowerCase())) ||
      (u.email && u.email.toLowerCase().includes(searchQuery.toLowerCase())) ||
      (u.id && u.id.toLowerCase().includes(searchQuery.toLowerCase())) ||
      (u.regNumber && u.regNumber.toLowerCase().includes(searchQuery.toLowerCase()));
    
    if (roleFilter === 'all') return matchesSearch;
    return matchesSearch && u.role === roleFilter;
  });

  return (
    <div className="flex-grow bg-slate-100 p-6 font-sans space-y-6">
      {/* Admin stats dashboard title banner */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-6 bg-white border border-slate-200 rounded-3xl shadow-sm">
        <div className="space-y-1">
          <div className="flex items-center gap-2">
            <span className="p-1 px-2.5 bg-rose-50 border border-rose-200 rounded-lg text-rose-700 text-[10px] font-black uppercase tracking-wider">
              System Admin Console
            </span>
            <span className="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
            <span className="text-xs text-slate-400 font-semibold">Security Gate Active</span>
          </div>
          <h2 className="text-2xl font-black text-slate-900 leading-tight">Swiftstudy Master Controls</h2>
          <p className="text-xs text-slate-500 font-medium">
            Monitor educational users portfolios, inspect generative lesson logs, support tickets, and recharge credits on-demand.
          </p>
        </div>
        
        <div className="flex items-center gap-2 shrink-0">
          <button 
            onClick={fetchAdminStats}
            className="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer border-none"
            title="Reload core stats"
          >
            <RefreshCw className="w-4 h-4" />
          </button>
          <div className="p-1 px-3 bg-indigo-50 border border-indigo-100 rounded-xl text-xs font-bold text-indigo-700">
            Role: Super Administrator
          </div>
        </div>
      </div>

      {loading ? (
        <div className="py-16 text-center space-y-4">
          <div className="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto" />
          <p className="text-sm font-black text-slate-800 animate-pulse">Syncing master database registries...</p>
        </div>
      ) : (
        <>
          {/* Quick Metrics Bento Grid */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="p-5 bg-white border border-slate-200 rounded-2xl flex items-center gap-4 shadow-xs">
              <div className="p-3 bg-blue-50 border border-blue-100 rounded-xl text-blue-600">
                <Users className="w-6 h-6" />
              </div>
              <div>
                <p className="text-[10px] uppercase font-black tracking-widest text-slate-400">Total Portals</p>
                <h3 className="text-xl font-black text-slate-900">{users.length} Users</h3>
                <p className="text-[10px] text-slate-400 font-medium font-mono">
                  S: {users.filter(u => u.role === 'student').length} • T: {users.filter(u => u.role === 'teacher').length}
                </p>
              </div>
            </div>

            <div className="p-5 bg-white border border-slate-200 rounded-2xl flex items-center gap-4 shadow-xs">
              <div className="p-3 bg-emerald-50 border border-emerald-100 rounded-xl text-emerald-600">
                <BookOpen className="w-6 h-6" />
              </div>
              <div>
                <p className="text-[10px] uppercase font-black tracking-widest text-slate-400">AI Logs</p>
                <h3 className="text-xl font-black text-slate-900">{documents.length} Units</h3>
                <p className="text-[10px] text-emerald-600 font-semibold">Generative files saved</p>
              </div>
            </div>

            <div className="p-5 bg-white border border-slate-200 rounded-2xl flex items-center gap-4 shadow-xs">
              <div className="p-3 bg-purple-50 border border-purple-100 rounded-xl text-purple-600">
                <GraduationCap className="w-6 h-6" />
              </div>
              <div>
                <p className="text-[10px] uppercase font-black tracking-widest text-slate-400">CBT Exams</p>
                <h3 className="text-xl font-black text-slate-900">{exams.length} CBTs</h3>
                <p className="text-[10px] text-slate-400 font-medium font-mono">
                  Pub: {exams.filter(e => e.isPublished).length} Exams
                </p>
              </div>
            </div>

            <div className="p-5 bg-white border border-slate-200 rounded-2xl flex items-center gap-4 shadow-xs">
              <div className="p-3 bg-red-50 border border-red-100 rounded-xl text-red-600">
                <MessageSquare className="w-6 h-6" />
              </div>
              <div>
                <p className="text-[10px] uppercase font-black tracking-widest text-slate-400">Support tickets</p>
                <h3 className="text-xl font-black text-slate-900">{feedback.length} Tickets</h3>
                <p className="text-[10px] text-red-600 font-semibold">Inquiries received</p>
              </div>
            </div>
          </div>

          {/* Navigation Tab selection */}
          <div className="bg-white border border-slate-200 rounded-2xl p-1.5 flex gap-1.5 shadow-xs max-w-md">
            <button
              onClick={() => setActiveTab('users')}
              className={`flex-1 py-2 text-xs font-black rounded-xl transition cursor-pointer border-none flex items-center justify-center gap-1.5 ${
                activeTab === 'users' 
                  ? 'bg-indigo-600 text-white shadow-sm' 
                  : 'bg-transparent text-slate-650 hover:bg-slate-50'
              }`}
            >
              <Users className="w-3.5 h-3.5" />
              User Profiles
            </button>
            <button
              onClick={() => setActiveTab('content')}
              className={`flex-1 py-2 text-xs font-black rounded-xl transition cursor-pointer border-none flex items-center justify-center gap-1.5 ${
                activeTab === 'content' 
                  ? 'bg-indigo-600 text-white shadow-sm' 
                  : 'bg-transparent text-slate-650 hover:bg-slate-50'
              }`}
            >
              <BookOpen className="w-3.5 h-3.5" />
              System Contents
            </button>
            <button
              onClick={() => setActiveTab('feedback')}
              className={`flex-1 py-2 text-xs font-black rounded-xl transition cursor-pointer border-none flex items-center justify-center gap-1.5 ${
                activeTab === 'feedback' 
                  ? 'bg-indigo-600 text-white shadow-sm' 
                  : 'bg-transparent text-slate-650 hover:bg-slate-50'
              }`}
            >
              <MessageSquare className="w-3.5 h-3.5" />
              Client Inquiries
            </button>
          </div>

          {/* Tab contents panel */}
          <div className="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
            
            {activeTab === 'users' && (
              <div className="p-6 space-y-6">
                {/* Filters search row */}
                <div className="flex flex-col sm:flex-row gap-3">
                  <div className="relative flex-1 flex items-center">
                    <Search className="absolute left-3.5 w-4 h-4 text-slate-400" />
                    <input 
                      type="text" 
                      placeholder="Search accounts catalog by Name, Email, ID or registration number..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="w-full bg-slate-50 border border-slate-250 rounded-xl pl-10 pr-4 py-2.5 text-xs font-semibold focus:outline-none focus:border-indigo-500"
                    />
                  </div>
                  
                  <select
                    value={roleFilter}
                    onChange={(e) => setRoleFilter(e.target.value)}
                    className="px-3 py-2.5 bg-slate-50 border border-slate-250 rounded-xl text-xs font-bold text-slate-650 focus:outline-none"
                  >
                    <option value="all">All Roles</option>
                    <option value="student">Student Account</option>
                    <option value="teacher">Educator Portfolio</option>
                    <option value="admin">Super Administrator</option>
                  </select>
                </div>

                {/* Users list table */}
                <div className="overflow-x-auto rounded-2xl border border-slate-150">
                  <table className="w-full text-left text-xs border-collapse">
                    <thead>
                      <tr className="bg-slate-50 text-slate-400 font-extrabold uppercase border-b border-slate-150 tracking-wider">
                        <th className="px-5 py-4">Participant User</th>
                        <th className="px-5 py-4">Assigned Role</th>
                        <th className="px-5 py-4">Register/ID</th>
                        <th className="px-5 py-4">Current Credits Balance</th>
                        <th className="px-5 py-4">Accredited Status</th>
                        <th className="px-5 py-4 text-right">Operations</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-150">
                      {filteredUsers.length === 0 ? (
                        <tr>
                          <td colSpan={6} className="text-center py-12 text-slate-400 font-medium">
                            No registered active profile accounts match your query criteria.
                          </td>
                        </tr>
                      ) : (
                        filteredUsers.map((item) => (
                          <tr key={item.id} className="hover:bg-slate-50/60 font-medium text-slate-650">
                            <td className="px-5 py-4">
                              <div className="space-y-0.5">
                                <span className="font-extrabold text-slate-900 block">{item.name || "N/A"}</span>
                                <span className="text-[11px] text-slate-400 block font-mono">{item.email}</span>
                              </div>
                            </td>
                            <td className="px-5 py-4">
                              <span className={`px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider ${
                                item.role === 'admin' 
                                  ? 'bg-rose-50 text-rose-600 border border-rose-200' 
                                  : item.role === 'teacher' 
                                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' 
                                    : 'bg-indigo-50 text-indigo-600 border border-indigo-200'
                              }`}>
                                {item.role === 'admin' ? 'admin' : item.role === 'teacher' ? 'educator' : 'student'}
                              </span>
                            </td>
                            <td className="px-5 py-4 font-mono">{item.regNumber || 'N/A'}</td>
                            <td className="px-5 py-4 text-emerald-700 font-extrabold">
                              ₦{(item.walletBalance || 0).toLocaleString()} <span className="text-[10px] text-slate-400">CR</span>
                            </td>
                            <td className="px-5 py-4">
                              <span className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold ${
                                item.isSuspended 
                                  ? 'bg-amber-50 text-amber-600 border border-amber-200' 
                                  : 'bg-emerald-50 text-emerald-600 border border-emerald-200'
                              }`}>
                                <span className={`h-1.5 w-1.5 rounded-full ${item.isSuspended ? 'bg-amber-500' : 'bg-emerald-500'}`} />
                                {item.isSuspended ? 'Suspended' : 'Clear & Active'}
                              </span>
                            </td>
                            <td className="px-5 py-4 text-right">
                              <div className="flex items-center justify-end gap-2">
                                <button 
                                  onClick={() => {
                                    setEditingUser(item);
                                    setWalletAmount('5000');
                                  }}
                                  className="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-250 text-slate-700 rounded-lg transition cursor-pointer border-none font-bold"
                                  title="Add credits & recharge wallet"
                                >
                                  <Coins className="w-3.5 h-3.5 inline mr-1 text-amber-500" />
                                  Action
                                </button>
                                <button 
                                  onClick={() => handleSuspendToggle(item)}
                                  className={`p-1.5 rounded-lg border-none cursor-pointer transition ${
                                    item.isSuspended 
                                      ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600' 
                                      : 'bg-amber-50 hover:bg-amber-100 text-amber-600'
                                  }`}
                                  title={item.isSuspended ? "Restore user privileges" : "Suspend student account"}
                                >
                                  {item.isSuspended ? <ShieldCheck className="w-3.5 h-3.5" /> : <Ban className="w-3.5 h-3.5" />}
                                </button>
                                <button 
                                  onClick={() => handleDeleteUser(item.id)}
                                  className="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg cursor-pointer border-none transition"
                                  title="Purge profile data"
                                >
                                  <Trash2 className="w-3.5 h-3.5" />
                                </button>
                              </div>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>

                {/* Edit & Recharge Dialog Modal Overlay */}
                <AnimatePresence>
                  {editingUser && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
                      <motion.div 
                        initial={{ opacity: 0, scale: 0.95 }}
                        animate={{ opacity: 1, scale: 1 }}
                        exit={{ opacity: 0, scale: 0.95 }}
                        className="bg-white rounded-3xl border border-slate-205 max-w-md w-full p-6 shadow-2xl space-y-6"
                      >
                        <div className="flex justify-between items-center pb-2 border-b border-slate-150">
                          <div>
                            <h4 className="font-black text-slate-900 text-lg">Adjust Portfolio Limits</h4>
                            <p className="text-xs text-slate-400 font-semibold">{editingUser.email}</p>
                          </div>
                          <button 
                            onClick={() => setEditingUser(null)}
                            className="p-1 px-2.5 bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-lg text-sm font-bold border-none cursor-pointer"
                          >
                            ✕
                          </button>
                        </div>

                        {actionError && (
                          <div className="p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs font-bold text-left">
                            ⚠️ {actionError}
                          </div>
                        )}

                        <form onSubmit={handleTopUpSubmit} className="space-y-4 text-left">
                          <div className="space-y-1.5">
                            <label className="text-xs font-black uppercase tracking-wider text-slate-500 block">Current Balance: </label>
                            <span className="p-3 bg-slate-50 border border-slate-150 rounded-xl block font-black text-slate-700">
                              ₦{(editingUser.walletBalance || 0).toLocaleString()} Credit Balance
                            </span>
                          </div>

                          <div className="space-y-1.5">
                            <label className="text-xs font-black uppercase tracking-wider text-slate-500 block">Top Up Wallet Cash Amount (₦):</label>
                            <input 
                              type="number" 
                              required
                              value={walletAmount}
                              onChange={(e) => setWalletAmount(e.target.value)}
                              placeholder="e.g. 10000"
                              className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold"
                            />
                            <p className="text-[10px] text-slate-400 font-medium">Recharging wallet adds requested funds directly to user credits balance.</p>
                          </div>

                          <div className="pt-2 flex gap-2">
                            <button 
                              type="submit"
                              disabled={submittingAction}
                              className="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-md transition cursor-pointer border-none"
                            >
                              {submittingAction ? 'Processing Top up...' : 'Confirm Recharge Wallet'}
                            </button>
                            <button 
                              type="button"
                              onClick={() => {
                                const newRole = editingUser.role === 'teacher' ? 'student' : 'teacher';
                                handleRoleChangeSubmit(editingUser.id, newRole);
                              }}
                              className="py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer border-none"
                            >
                              Toggle Role to {editingUser.role === 'teacher' ? 'Student' : 'Educator'}
                            </button>
                          </div>
                        </form>
                      </motion.div>
                    </div>
                  )}
                </AnimatePresence>
              </div>
            )}

            {activeTab === 'content' && (
              <div className="p-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  {/* Lesson Notes/Plans list summary */}
                  <div className="space-y-3">
                    <div className="flex justify-between items-center border-b border-slate-200 pb-2">
                      <h4 className="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-1.5">
                        <BookOpen className="w-4 h-4 text-emerald-600" />
                        AI GENERATIVE SYLLABUS LOGS ({documents.length})
                      </h4>
                    </div>
                    {documents.length === 0 ? (
                      <p className="text-xs text-slate-400 font-medium py-6 text-center">No generative documentation materials cached in system logs.</p>
                    ) : (
                      <div className="space-y-2 max-h-[400px] overflow-y-auto pr-1">
                        {documents.map((doc) => (
                          <div key={doc.id} className="p-3 bg-slate-50 border border-slate-150 rounded-xl space-y-1 text-xs">
                            <div className="flex justify-between">
                              <span className="p-1 px-2 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-md text-[9px] font-black uppercase uppercase tracking-wider">
                                {doc.category || "AI Resource"}
                              </span>
                              <span className="text-[10px] text-slate-400 font-mono">
                                {doc.createdAt ? new Date(doc.createdAt).toLocaleDateString() : "N/A"}
                              </span>
                            </div>
                            <h5 className="font-extrabold text-slate-900 leading-snug">{doc.title}</h5>
                            <p className="text-[10px] text-slate-500 font-semibold font-sans">
                              Subject: <strong className="text-slate-700">{doc.subject || "Academic"}</strong> • Owner UUID: <span className="font-mono">{doc.userId}</span>
                            </p>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>

                  {/* CBT Exams list */}
                  <div className="space-y-3">
                    <div className="flex justify-between items-center border-b border-slate-200 pb-2">
                      <h4 className="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-1.5">
                        <GraduationCap className="w-4 h-4 text-indigo-600" />
                        PUBLISHED CBT EXAM ROOMS ({exams.length})
                      </h4>
                    </div>
                    {exams.length === 0 ? (
                      <p className="text-xs text-slate-400 font-medium py-6 text-center">No examination suites published by educators currently.</p>
                    ) : (
                      <div className="space-y-2 max-h-[400px] overflow-y-auto pr-1">
                        {exams.map((exam) => (
                          <div key={exam.id} className="p-3 bg-slate-50 border border-slate-150 rounded-xl space-y-1 text-xs">
                            <div className="flex justify-between items-center">
                              <span className="font-extrabold text-slate-900 leading-snug">{exam.title}</span>
                              <span className={`px-1.5 py-0.5 rounded text-[9px] font-black uppercase ${
                                exam.isPublished 
                                  ? 'bg-emerald-50 text-emerald-600 border border-emerald-250' 
                                  : 'bg-amber-50 text-amber-600 border border-amber-250'
                              }`}>
                                {exam.isPublished ? 'Live' : 'Draft'}
                              </span>
                            </div>
                            <p className="text-[10px] text-slate-500 font-medium leading-snug">
                              Subject: <span className="text-slate-800 font-extrabold">{exam.subject} ({exam.level})</span> • Questions: {exam.questions?.length || 0}
                            </p>
                            <p className="text-[9px] text-slate-400 font-sans">
                              Created by: <span className="font-bold text-indigo-600">{exam.creatorName}</span> ({exam.creatorId})
                            </p>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'feedback' && (
              <div className="p-6">
                <h4 className="text-xs font-black text-slate-800 uppercase tracking-widest border-b border-slate-150 pb-2 mb-4 flex items-center gap-1.5">
                  <Mail className="w-4 h-4 text-red-600 animate-pulse" />
                  CLIENT REVIEWS & SUPPORT DESK TICKETS ({feedback.length})
                </h4>
                
                {feedback.length === 0 ? (
                  <div className="p-12 text-center text-slate-400 font-medium text-xs space-y-2">
                    <CheckCircle className="w-8 h-8 text-emerald-500 mx-auto" />
                    <p>Hooray! No pending support inquiries or complaints filed currently.</p>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {feedback.map((item) => (
                      <div key={item.id} className="p-4 bg-slate-50 border border-slate-150 rounded-2xl space-y-3 relative">
                        <button 
                          onClick={() => handleDeleteFeedback(item.id)}
                          className="absolute top-3 right-3 p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg border-none cursor-pointer transition"
                          title="Archive inquiry"
                        >
                          <Trash className="w-3.5 h-3.5" />
                        </button>
                        
                        <div className="space-y-1">
                          <span className="text-[10px] text-slate-400 font-mono block">
                            Inquiry ID: {item.id} • {item.date ? new Date(item.date).toLocaleString() : 'N/A'}
                          </span>
                          <strong className="text-slate-900 block font-black">{item.name}</strong>
                          <span className="text-xs text-indigo-700 font-semibold font-mono block">{item.email}</span>
                        </div>

                        <p className="text-xs p-3.5 bg-white border border-slate-100 rounded-xl text-slate-650 leading-relaxed font-medium">
                          {item.message}
                        </p>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

          </div>
        </>
      )}
    </div>
  );
}
