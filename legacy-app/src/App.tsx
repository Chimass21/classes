import { useState, useEffect, FormEvent } from 'react';
import { Sparkles, BrainCircuit, School, BookOpen, GraduationCap, ArrowRight, Check, Star, Shield, MessageSquare, Phone, X } from 'lucide-react';
import LandingPage from './components/LandingPage';
import StudentDashboard from './components/StudentDashboard';
import TeacherDashboard from './components/TeacherDashboard';
import AdminDashboard from './components/AdminDashboard';
import ExamEngine from './components/ExamEngine';
import FloatingSupportChat from './components/FloatingSupportChat';
import { Exam } from './types';
import { Lock, Mail, Loader, KeyRound, Key, ShieldCheck, UserCheck } from 'lucide-react';

export default function App() {
  const [currentUser, setCurrentUser] = useState<any>(null);
  const [currentView, setCurrentView] = useState<'landing' | 'portal'>('landing');
  const [activePortalPerspective, setActivePortalPerspective] = useState<'student' | 'teacher' | 'admin'>(() => {
    return (localStorage.getItem('swiftstudy_perspective') as 'student' | 'teacher' | 'admin') || 'student';
  });
  const [selectedCBTExam, setSelectedCBTExam] = useState<Exam | null>(null);
  const [sessionLoading, setSessionLoading] = useState(true);
  const [guestStudentName, setGuestStudentName] = useState('');
  const [admissionStep, setAdmissionStep] = useState<'inputName' | 'startExam'>('inputName');
  const [activeExamStudentName, setActiveExamStudentName] = useState('');
  const [guestExamCompleted, setGuestExamCompleted] = useState<{ studentName: string; examTitle: string } | null>(null);

  // Security checking states for CBT attempts & standard free gate access
  const [candidateAuthError, setCandidateAuthError] = useState('');
  const [initiatingAttempt, setInitiatingAttempt] = useState(false);

  // Authentication Modal States
  const [showAuthModal, setShowAuthModal] = useState(false);
  const [authMode, setAuthMode] = useState<'login' | 'signup' | 'forgot'>('login');
  const [authEmail, setAuthEmail] = useState('');
  const [authPassword, setAuthPassword] = useState('');
  const [authConfirmPassword, setAuthConfirmPassword] = useState('');
  const [authName, setAuthName] = useState('');
  const [signupRole, setSignupRole] = useState<'student' | 'teacher'>('student');
  const [authError, setAuthError] = useState('');
  const [authSuccess, setAuthSuccess] = useState('');
  const [authLoading, setAuthLoading] = useState(false);

  const readCookie = (name: string) => {
    const value = document.cookie
      .split('; ')
      .find((row) => row.startsWith(`${name}=`))
      ?.split('=')[1];
    return value ? decodeURIComponent(value) : '';
  };

  // Synchronize client-side public perspective session cookie and data context
  useEffect(() => {
    localStorage.setItem('swiftstudy_perspective', activePortalPerspective);
    if (currentUser?.id) {
      document.cookie = `brain_user_id=${encodeURIComponent(currentUser.id)}; path=/; max-age=31536000`;
    }
    
    const syncSession = async () => {
      try {
        const res = await fetch('/api/auth/session');
        if (res.ok) {
          const data = await res.json();
          if (data.user) {
            setCurrentUser(data.user);
            setCurrentView('portal');
            // Sync perspective role
            if (data.user.role === 'admin') {
              setActivePortalPerspective('admin');
            } else if (data.user.role === 'teacher') {
              setActivePortalPerspective('teacher');
            } else {
              setActivePortalPerspective('student');
            }
          }
        }
      } catch (err) {
        console.error('Session sync error:', err);
      } finally {
        setSessionLoading(false);
      }
    };

    const existingUserId = readCookie('brain_user_id');
    if (existingUserId && !existingUserId.startsWith('public_')) {
      syncSession();
    } else {
      setSessionLoading(false);
    }
  }, [activePortalPerspective, currentUser?.id]);

  const handleLogout = async () => {
    try {
      await fetch('/api/auth/logout', { method: 'POST' });
      setCurrentUser(null);
      setActivePortalPerspective('student');
      setCurrentView('landing');
    } catch (e) {
      console.error("Logout simulation error:", e);
    }
  };

  const handleTriggerAuthModal = (role?: 'student' | 'teacher' | 'admin') => {
    if (role && role !== 'admin') {
      setSignupRole(role);
    }
    setAuthMode('login');
    setAuthError('');
    setAuthSuccess('');
    setShowAuthModal(true);
  };

  const handleAuthSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setAuthError('');
    setAuthSuccess('');
    setAuthLoading(true);

    try {
      if (authMode === 'login') {
        const res = await fetch('/api/auth/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: authEmail, password: authPassword }),
        });
        const data = await res.json();
        if (res.ok && data.success) {
          setCurrentUser(data.user);
          setAuthEmail('');
          setAuthPassword('');
          setShowAuthModal(false);
          
          if (data.user.role === 'admin') {
            setActivePortalPerspective('admin');
          } else if (data.user.role === 'teacher') {
            setActivePortalPerspective('teacher');
          } else {
            setActivePortalPerspective('student');
          }
          setCurrentView('portal');
        } else {
          setAuthError(data.error || 'Authentication sequence failed.');
        }
      } else if (authMode === 'signup') {
        if (authPassword !== authConfirmPassword) {
          setAuthError('Passwords do not match.');
          setAuthLoading(false);
          return;
        }
        const res = await fetch('/api/auth/register', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            name: authName,
            email: authEmail,
            role: signupRole,
            password: authPassword,
            confirmPassword: authConfirmPassword,
          }),
        });
        const data = await res.json();
        if (res.ok) {
          setAuthSuccess('Account registered successfully! Securely logging you in...');
          
          const logInRes = await fetch('/api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: authEmail, password: authPassword }),
          });
          const logInData = await logInRes.json();
          if (logInRes.ok && logInData.success) {
            setCurrentUser(logInData.user);
            setAuthEmail('');
            setAuthPassword('');
            setAuthConfirmPassword('');
            setAuthName('');
            setShowAuthModal(false);
            setActivePortalPerspective(signupRole);
            setCurrentView('portal');
          } else {
            setAuthMode('login');
          }
        } else {
          setAuthError(data.error || 'Direct registration failed.');
        }
      } else if (authMode === 'forgot') {
        const res = await fetch('/api/auth/reset', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: authEmail }),
        });
        const data = await res.json();
        if (res.ok) {
          setAuthSuccess('Verification instruction successfully sent! Please check your inbox.');
        } else {
          setAuthError(data.error || 'Failed to request reset guidelines.');
        }
      }
    } catch (err: any) {
      setAuthError('Platform connection failed: ' + (err.message || err));
    } finally {
      setAuthLoading(false);
    }
  };

  // Parse custom parameters on initial mount (e.g., student joins exam link from url query or hash)
  const parseExamLinkQuery = async (examsList: Exam[]) => {
    const hash = window.location.hash || '';
    const params = new URLSearchParams(window.location.search);
    const examIdFromQuery = params.get('examId');

    let examId = '';
    if (hash.startsWith('#/exam/')) {
      examId = hash.replace('#/exam/', '');
    } else if (examIdFromQuery) {
      examId = examIdFromQuery;
    }

    if (examId) {
      const match = examsList.find((e) => e.id === examId);
      if (match) {
        setSelectedCBTExam(match);
      }
    }
  };

  const checkUserSession = async () => {
    setSessionLoading(true);
    try {
      // Fetch exams List to check instant link joining hooks
      const examRes = await fetch('/api/exams');
      if (examRes.ok) {
        const examData = await examRes.json();
        const publishedExams = examData.exams || [];
        parseExamLinkQuery(publishedExams);
      }
    } catch (e) {
      console.error('Session sync offline:', e);
    } finally {
      setSessionLoading(false);
    }
  };

  useEffect(() => {
    checkUserSession();
  }, []);

  if (sessionLoading) {
    return (
      <div className="min-h-screen bg-slate-50 flex flex-col items-center justify-center font-sans space-y-4">
        <div className="relative">
          <span className="flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 text-white font-bold text-2xl shadow-xl animate-bounce">
            S
          </span>
          <span className="absolute inset-0 rounded-2xl bg-indigo-500 animate-ping opacity-25" />
        </div>
        <div className="text-center">
          <p className="text-sm font-black text-slate-800">Bootstrapping school structures...</p>
          <p className="text-xs text-slate-400 font-semibold mt-1">Contact: nwaigboaugust@gmail.com</p>
        </div>
      </div>
    );
  }

  // Active CBT testing console - runs full-screen
  if (selectedCBTExam) {
    if (!activeExamStudentName) {
      const handleProceedAndStart = async (e: FormEvent) => {
        e.preventDefault();
        const trimmed = guestStudentName.trim();
        if (!trimmed) return;

        setCandidateAuthError('');
        setInitiatingAttempt(true);

        try {
          // 1. Verify Attempt limits per name
          const checkRes = await fetch(`/api/exams/${selectedCBTExam.id}/check-attempts?studentName=${encodeURIComponent(trimmed)}`);
          if (!checkRes.ok) {
            throw new Error("Could not verify attempt status on core server.");
          }
          const checkData = await checkRes.json();
          if (checkData.attempts >= 2) {
            setCandidateAuthError(`Access Denied: '${trimmed}' has already taken this CBT exam 2 times. The maximum attempt threshold is strictly enforced.`);
            setInitiatingAttempt(false);
            return;
          }

          // 2. Take exam initiation action
          const startRes = await fetch(`/api/exams/${selectedCBTExam.id}/start-attempt`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ studentName: trimmed })
          });

          const startData = await startRes.json();
          if (!startRes.ok) {
            setCandidateAuthError(startData.error || `Registration gate failure. Please contact administrator.`);
            setInitiatingAttempt(false);
            return;
          }

          setActiveExamStudentName(trimmed);
          setAdmissionStep('startExam');
        } catch (err: any) {
          setCandidateAuthError(err.message || "An unexpected error occurred. Please try again.");
        } finally {
          setInitiatingAttempt(false);
        }
      };

      return (
        <div className="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-4">
          <form
            onSubmit={handleProceedAndStart}
            className="max-w-md w-full bg-white p-8 rounded-3xl border border-slate-150 text-center space-y-6 shadow-xl"
          >
            <div className="w-16 h-16 bg-gradient-to-tr from-violet-600 to-indigo-600 text-white rounded-2xl flex items-center justify-center text-3xl mx-auto shadow-md">
              ✍
            </div>
            <div className="space-y-1">
              <h1 className="text-xl font-black text-slate-900 font-sans">Enter Your Name & Start Exam</h1>
              <p className="text-xs text-slate-500 font-medium font-sans">
                Please enter your name below to unlock your standard CBT exam access immediately.
              </p>
            </div>

            {candidateAuthError && (
              <div className="p-3.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs text-left rounded-2xl font-bold font-sans">
                {candidateAuthError}
              </div>
            )}

            <div className="p-4 bg-slate-50 border border-slate-200 rounded-2xl text-left space-y-2 text-xs font-sans">
              <p className="font-semibold text-slate-600"><strong className="text-slate-800">CBT Exam:</strong> {selectedCBTExam.title}</p>
              <p className="font-semibold text-slate-600"><strong className="text-slate-800">Subject:</strong> {selectedCBTExam.subject} ({selectedCBTExam.level})</p>
              <p className="font-semibold text-slate-600"><strong className="text-slate-800">Duration:</strong> {selectedCBTExam.duration} Minutes</p>
              <p className="font-bold text-emerald-600 p-1 bg-emerald-50/50 rounded-lg text-[10px] uppercase tracking-wider block text-center">CBT Standard Taking: 100% FREE</p>
            </div>
            
            <div className="space-y-2 text-left">
              <label className="text-xs font-black text-slate-600 uppercase tracking-wider block">
                Your Full Name:
              </label>
              <input
                type="text"
                required
                disabled={initiatingAttempt}
                placeholder="e.g., John Doe"
                value={guestStudentName}
                onChange={(ev) => setGuestStudentName(ev.target.value)}
                className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition text-sm"
              />
            </div>

            <div className="flex flex-col gap-2 pt-2">
              <button
                type="submit"
                disabled={!guestStudentName.trim() || initiatingAttempt}
                className="w-full py-3 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 disabled:opacity-50 text-white font-extrabold text-sm rounded-xl shadow-lg transition cursor-pointer"
              >
                {initiatingAttempt ? "Authenticating Entry..." : "Start Exam (Free)"}
              </button>
              <button
                type="button"
                disabled={initiatingAttempt}
                onClick={() => {
                  setSelectedCBTExam(null);
                  setGuestStudentName('');
                  setActiveExamStudentName('');
                  setAdmissionStep('inputName');
                  setCandidateAuthError('');
                  window.location.hash = '';
                  if (window.location.search) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                  }
                }}
                className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
              >
                Cancel and Go Back
              </button>
            </div>
            
            <p className="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">
              Secure CBT Engine by Swiftstudy
            </p>
          </form>
        </div>
      );
    }

    return (
      <ExamEngine
        exam={selectedCBTExam}
        studentUser={{
          id: currentUser?.id && !currentUser?.isGuest ? currentUser.id : 'guest_' + Math.random().toString(36).substring(2, 9),
          name: activeExamStudentName,
          email: `${activeExamStudentName.toLowerCase().replace(/\s+/g, '')}@student.cbt`,
          role: 'student',
          isGuest: true,
          walletBalance: 0,
        }}
        onExit={() => {
          if (!currentUser) {
            setGuestExamCompleted({
              studentName: activeExamStudentName,
              examTitle: selectedCBTExam.title,
            });
          }
          setSelectedCBTExam(null);
          setGuestStudentName('');
          setActiveExamStudentName('');
          setAdmissionStep('inputName');
          window.location.hash = '';
          if (window.location.search) {
            window.history.replaceState({}, document.title, window.location.pathname);
          }
        }}
      />
    );
  }

  if (guestExamCompleted) {
    return (
      <div className="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-4">
        <div className="max-w-md w-full bg-white p-8 rounded-3xl border border-slate-150 text-center space-y-6 shadow-xl">
          <div className="w-16 h-16 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center text-3xl mx-auto shadow-md">
            ✓
          </div>
          <div className="space-y-2">
            <h1 className="text-xl font-black text-slate-900 leading-tight">CBT Session Completed</h1>
            <p className="text-xs text-slate-500 font-semibold">
              Thank you, <strong className="text-slate-800 font-extrabold">{guestExamCompleted.studentName}</strong>! Your CBT answers for <strong className="text-slate-800 font-extrabold">{guestExamCompleted.examTitle}</strong> have been saved and scored.
            </p>
          </div>
          <div className="p-4 bg-slate-50 rounded-2xl border border-slate-150 text-xs text-slate-650 space-y-2 font-medium">
            <p>Your results have been securely archived. Your principal assessor and teacher can now view your academic scores.</p>
            <p className="text-slate-400 text-[10px] uppercase font-bold tracking-wider pt-1">You may now safely close this browser window or tab.</p>
          </div>
          <button
            onClick={() => {
              setGuestExamCompleted(null);
            }}
            className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black rounded-xl transition cursor-pointer border-none"
          >
            Go to School Homepage
          </button>
        </div>
      </div>
    );
  }

  // --- CORE SYSTEM DASHBOARDS VIEW WITH EASY PERSPECTIVE SWITCHING & HOMEPAGE NAVIGATION ---
  if (currentView === 'portal') {
    const portalUser = currentUser || {
      id: activePortalPerspective === 'teacher' ? 'public_teacher' : 'public_student',
      email: activePortalPerspective === 'teacher' ? 'educator@swiftstudy.edu' : 'student@swiftstudy.edu',
      name: activePortalPerspective === 'teacher' ? 'Guest Educator' : 'Guest Scholar',
      role: activePortalPerspective,
      walletBalance: 100000,
      regNumber: 'SS-2026-GUEST',
      classLevel: 'Grade 10',
      schoolName: 'Swiftstudy Academy'
    };

    return (
      <div className="min-h-screen flex flex-col bg-slate-50 font-sans selection:bg-indigo-500 selection:text-white">
        {/* Simple & Clean Status Header representing correct Persona */}
        <div className="bg-slate-900 border-b border-slate-950 text-slate-100 px-6 py-3.5 flex flex-col md:flex-row items-center justify-between gap-4 shadow-md shrink-0">
          <div className="flex items-center gap-2.5">
            <span className="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-violet-600 to-indigo-600 text-white font-black text-md">
              S
            </span>
            <div className="text-xs">
              <span className="text-slate-400 font-medium font-sans">Active Profile: </span>
              <strong className="text-white font-extrabold font-sans pr-1">
                {portalUser.name}
              </strong>
              <span className="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-300">
                {activePortalPerspective === 'admin' ? 'Super Administrator' : activePortalPerspective === 'teacher' ? 'Educator Portfolio' : 'Candidate Student'}
              </span>
              {currentUser ? (
                <span className="ml-2 px-1.5 py-0.5 rounded text-[9px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 tracking-widest uppercase">
                  Signed In
                </span>
              ) : (
                <span className="ml-2 px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30 tracking-widest uppercase">
                  Default Guest Access
                </span>
              )}
            </div>
          </div>

          <div className="flex items-center gap-3">
            {activePortalPerspective === 'student' && portalUser && portalUser.regNumber && (
              <span className="text-slate-400 text-xs font-mono font-bold tracking-wider uppercase">
                ID: {portalUser.regNumber}
              </span>
            )}
            
            {!currentUser ? (
              <button
                onClick={() => handleTriggerAuthModal(activePortalPerspective === 'admin' ? 'teacher' : activePortalPerspective)}
                className="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-black transition cursor-pointer border-none shadow-md"
              >
                Sign In / Register
              </button>
            ) : (
              <button
                onClick={handleLogout}
                className="px-3.5 py-1.5 bg-rose-600/10 hover:bg-rose-600/20 text-rose-400 rounded-lg text-xs font-black transition cursor-pointer border-none"
              >
                Logout
              </button>
            )}

            <button
              onClick={() => setCurrentView('landing')}
              className="px-3.5 py-1.5 bg-slate-850 hover:bg-slate-800 rounded-lg text-slate-300 hover:text-white text-xs font-semibold transition cursor-pointer border-none"
            >
              Back to Home
            </button>
          </div>
        </div>

        {/* Unified Portal Selector Bar */}
        <div className="bg-slate-800 border-b border-slate-950 px-6 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0">
          <div className="flex items-center gap-2">
            <span className="text-[10px] uppercase font-black text-slate-400 tracking-wider">Active Workspace View:</span>
            <span className="px-2 py-0.5 bg-indigo-500/20 text-indigo-300 rounded-md text-[10px] font-black uppercase tracking-wider border border-indigo-500/30 animate-pulse">
              {activePortalPerspective === 'admin' ? "Admin Portal" : activePortalPerspective === 'teacher' ? "Teacher's Portal" : "Student Portal"}
            </span>
          </div>

          <div className="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
            <button
              onClick={() => setActivePortalPerspective('student')}
              className={`px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer border-none ${
                activePortalPerspective === 'student'
                  ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-white/10'
                  : 'bg-slate-900 text-slate-300 hover:bg-slate-755 hover:text-white'
              }`}
            >
              <GraduationCap className="w-3.5 h-3.5 text-indigo-400" />
              Student Portal
            </button>
            <button
              onClick={() => setActivePortalPerspective('teacher')}
              className={`px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer border-none ${
                activePortalPerspective === 'teacher'
                  ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-white/10'
                  : 'bg-slate-900 text-slate-300 hover:bg-slate-755 hover:text-white'
              }`}
            >
              <BookOpen className="w-3.5 h-3.5 text-indigo-400" />
              Teacher's Portal
            </button>
            {currentUser && currentUser.role === 'admin' && (
              <button
                onClick={() => setActivePortalPerspective('admin')}
                className={`px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer border-none ${
                  activePortalPerspective === 'admin'
                    ? 'bg-rose-600 text-white shadow-sm ring-1 ring-white/10'
                    : 'bg-slate-900 text-slate-300 hover:bg-slate-755 hover:text-white'
                }`}
              >
                <Shield className="w-3.5 h-3.5 text-rose-450 animate-pulse" />
                Admin Console
              </button>
            )}
          </div>
        </div>

        <div className="flex-grow flex flex-col">
          {activePortalPerspective === 'admin' ? (
            <AdminDashboard
              user={portalUser}
              onLogout={handleLogout}
            />
          ) : activePortalPerspective === 'teacher' ? (
            <TeacherDashboard
              user={portalUser}
              onLogout={handleLogout}
            />
          ) : (
            <StudentDashboard
              user={portalUser}
              onLogout={handleLogout}
              onTakeExam={(exam) => setSelectedCBTExam(exam)}
            />
          )}
        </div>
        
        <FloatingSupportChat />
      </div>
    );
  }

  // C: PUBLIC HOMEPAGE & CBT LINK TRIGGERS
  return (
    <>
      <LandingPage
        onGetStarted={() => {
          handleTriggerAuthModal('teacher');
        }}
        onLoginClick={(role) => {
          handleTriggerAuthModal(role);
        }}
        onSelectExam={(exam) => setSelectedCBTExam(exam)}
      />

      <FloatingSupportChat />

      {/* SYSTEM AUTHENTICATION MODAL DIALOG (Sign Up, Sign In, Reset password) */}
      {showAuthModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm font-sans">
          <div className="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-8 shadow-2xl relative space-y-6">
            <button
              onClick={() => setShowAuthModal(false)}
              className="absolute top-4 right-4 p-2 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-full cursor-pointer transition border-none"
            >
              <X className="w-4 h-4" />
            </button>

            {/* Modal Heading Header */}
            <div className="text-center space-y-2">
              <span className="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 text-white font-black text-2xl shadow-md mb-2">
                S
              </span>
              <h3 className="text-xl font-black text-slate-900 leading-tight">
                {authMode === 'login' ? 'Welcome Back to Swiftstudy' : authMode === 'signup' ? 'Create Educator or Student Account' : 'Recover Access Credentials'}
              </h3>
              <p className="text-xs text-slate-400 font-semibold">
                {authMode === 'login' 
                  ? 'Log in to sync syllabus, CBT tests registries, and load class notes.' 
                  : authMode === 'signup' 
                    ? 'Get your customized Nigeria and WAEC/NECO educational dashboard.' 
                    : 'Enter registered email address to continue validation.'}
              </p>
            </div>

            {/* Error or Success banners */}
            {authError && (
              <div className="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold rounded-2xl text-left">
                ⚠️ {authError}
              </div>
            )}
            {authSuccess && (
              <div className="p-3 bg-emerald-50 border border-emerald-250 text-emerald-700 text-xs font-bold rounded-2xl text-left">
                ✓ {authSuccess}
              </div>
            )}

            {/* Tabs for switching Modal state (Not shown in forgot mode) */}
            {authMode !== 'forgot' && (
              <div className="bg-slate-100 p-1 rounded-2xl flex gap-1 text-center font-bold text-xs">
                <button
                  type="button"
                  onClick={() => {
                    setAuthMode('login');
                    setAuthError('');
                    setAuthSuccess('');
                  }}
                  className={`flex-1 py-1.5 rounded-xl transition cursor-pointer border-none ${
                    authMode === 'login' ? 'bg-white text-slate-900 shadow-sm' : 'bg-transparent text-slate-500 hover:text-slate-800'
                  }`}
                >
                  Sign In
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setAuthMode('signup');
                    setAuthError('');
                    setAuthSuccess('');
                  }}
                  className={`flex-1 py-1.5 rounded-xl transition cursor-pointer border-none ${
                    authMode === 'signup' ? 'bg-white text-slate-900 shadow-sm' : 'bg-transparent text-slate-500 hover:text-slate-800'
                  }`}
                >
                  Sign Up
                </button>
              </div>
            )}

            {/* Operational Authentication Form */}
            <form onSubmit={handleAuthSubmit} className="space-y-4 text-left">
              
              {authMode === 'signup' && (
                <div className="space-y-1">
                  <label className="text-xs font-black text-slate-600 block">Your Full Name:</label>
                  <input
                    type="text"
                    required
                    maxLength={70}
                    placeholder="e.g., Austin Nwaigbo"
                    value={authName}
                    onChange={(ev) => setAuthName(ev.target.value)}
                    className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none text-xs"
                  />
                </div>
              )}

              <div className="space-y-1">
                <label className="text-xs font-black text-slate-600 block">Academic Email Address:</label>
                <div className="relative flex items-center">
                  <Mail className="absolute left-3 w-4 h-4 text-slate-400" />
                  <input
                    type="email"
                    required
                    placeholder="e.g., educator@swiftstudy.edu"
                    value={authEmail}
                    onChange={(ev) => setAuthEmail(ev.target.value)}
                    className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none text-xs"
                  />
                </div>
              </div>

              {authMode !== 'forgot' && (
                <div className="space-y-1">
                  <label className="text-xs font-black text-slate-600 block">Account Password:</label>
                  <div className="relative flex items-center">
                    <Lock className="absolute left-3 w-4 h-4 text-slate-400" />
                    <input
                      type="password"
                      required
                      minLength={8}
                      placeholder="At least 8 characters"
                      value={authPassword}
                      onChange={(ev) => setAuthPassword(ev.target.value)}
                      className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none text-xs"
                    />
                  </div>
                </div>
              )}

              {authMode === 'signup' && (
                <>
                  <div className="space-y-1">
                    <label className="text-xs font-black text-slate-600 block">Confirm Password:</label>
                    <div className="relative flex items-center">
                      <Lock className="absolute left-3 w-4 h-4 text-slate-400" />
                      <input
                        type="password"
                        required
                        minLength={8}
                        placeholder="Re-enter password for clearance"
                        value={authConfirmPassword}
                        onChange={(ev) => setAuthConfirmPassword(ev.target.value)}
                        className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none text-xs"
                      />
                    </div>
                  </div>

                  <div className="space-y-1">
                    <label className="text-xs font-black text-slate-600 block">Describe Student or Teacher Role: </label>
                    <div className="grid grid-cols-2 gap-2 text-center text-xs font-semibold">
                      <button
                        type="button"
                        onClick={() => setSignupRole('student')}
                        className={`py-2 px-3 border rounded-xl cursor-pointer transition ${
                          signupRole === 'student' 
                            ? 'bg-indigo-50 border-indigo-400 text-indigo-700 font-extrabold' 
                            : 'bg-slate-50 border-slate-200 text-slate-500 hover:bg-slate-100'
                        }`}
                      >
                        🎓 Student Candidate
                      </button>
                      <button
                        type="button"
                        onClick={() => setSignupRole('teacher')}
                        className={`py-2 px-3 border rounded-xl cursor-pointer transition ${
                          signupRole === 'teacher' 
                            ? 'bg-indigo-50 border-indigo-400 text-indigo-700 font-extrabold' 
                            : 'bg-slate-50 border-slate-200 text-slate-500 hover:bg-slate-100'
                        }`}
                      >
                        ✍ Professional Teacher
                      </button>
                    </div>
                  </div>
                </>
              )}

              {/* Forgot password switch trigger link */}
              {authMode === 'login' && (
                <div className="text-right">
                  <button
                    type="button"
                    onClick={() => {
                      setAuthMode('forgot');
                      setAuthError('');
                      setAuthSuccess('');
                    }}
                    className="text-xs font-black text-slate-500 hover:text-indigo-600 transition cursor-pointer bg-transparent border-none p-0"
                  >
                    Forgot registration password?
                  </button>
                </div>
              )}

              {authMode === 'forgot' && (
                <div className="text-left">
                  <button
                    type="button"
                    onClick={() => {
                      setAuthMode('login');
                      setAuthError('');
                      setAuthSuccess('');
                    }}
                    className="text-xs font-black text-indigo-600 hover:text-indigo-700 transition cursor-pointer bg-transparent border-none p-0"
                  >
                    Back to Log In
                  </button>
                </div>
              )}

              {/* Submit trigger button */}
              <button
                type="submit"
                disabled={authLoading}
                className="w-full py-3 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-lg transition cursor-pointer uppercase tracking-wider border-none text-center flex items-center justify-center gap-2"
              >
                {authLoading && (
                  <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                )}
                {authMode === 'login' ? 'Validate Credentials' : authMode === 'signup' ? 'Create Academic Profile' : 'Recover My Account'}
              </button>
              
              <div className="p-4 bg-slate-50 rounded-2xl border border-slate-150 text-[10px] text-slate-400 leading-snug font-medium font-mono text-center">
                <strong>System notice:</strong> To login as Platform Admin, use:<br/>
                <span className="text-indigo-600">admin@gmail.com</span> / <span className="text-red-650 font-bold">password</span>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  );
}
