import React, { useState, useEffect } from 'react';
import { Sparkles, BrainCircuit, School, BookOpen, GraduationCap, ArrowRight, Check, Star, Shield, MessageSquare, Phone, X, Search, Laptop, KeyRound } from 'lucide-react';
import { motion } from 'motion/react';
import { VoiceInputButton } from './VoiceInputButton';

interface LandingPageProps {
  onGetStarted: () => void;
  onLoginClick: (role: 'student' | 'teacher' | 'admin') => void;
  onSelectExam?: (exam: any) => void;
}

export default function LandingPage({ onGetStarted, onLoginClick, onSelectExam }: LandingPageProps) {
  const [activeExams, setActiveExams] = useState<any[]>([]);
  const [allExams, setAllExams] = useState<any[]>([]);
  const [examCodeInput, setExamCodeInput] = useState('');
  const [codeError, setCodeError] = useState('');
  const [codeSuccess, setCodeSuccess] = useState('');
  const [loadingExams, setLoadingExams] = useState(false);

  // Support inquiry contact form states
  const [feedbackName, setFeedbackName] = useState('');
  const [feedbackEmail, setFeedbackEmail] = useState('');
  const [feedbackMsg, setFeedbackMsg] = useState('');
  const [feedbackStatus, setFeedbackStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle');

  const handleFeedbackSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!feedbackName.trim() || !feedbackEmail.trim() || !feedbackMsg.trim()) return;
    setFeedbackStatus('loading');
    try {
      const response = await fetch('/api/feedback', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          name: feedbackName,
          email: feedbackEmail,
          message: feedbackMsg
        })
      });
      if (response.ok) {
        setFeedbackStatus('success');
        setFeedbackName('');
        setFeedbackEmail('');
        setFeedbackMsg('');
        setTimeout(() => setFeedbackStatus('idle'), 6000);
      } else {
        setFeedbackStatus('error');
      }
    } catch {
      setFeedbackStatus('error');
    }
  };

  useEffect(() => {
    setLoadingExams(true);
    fetch('/api/exams')
      .then((res) => res.json())
      .then((data) => {
        if (data.exams) {
          setAllExams(data.exams);
          setActiveExams(data.exams.filter((e: any) => e.isPublished));
        }
      })
      .catch((err) => console.error('Failed to load active exams stream:', err))
      .finally(() => setLoadingExams(false));
  }, []);

  const handleJoinByCode = (e: React.FormEvent) => {
    e.preventDefault();
    setCodeError('');
    setCodeSuccess('');
    const input = examCodeInput.trim();
    if (!input) return;

    // Detect if they pasted a full link, e.g. https://.../?examId=exam_123 or exam_123
    let examId = input;
    if (input.includes('examId=')) {
      const parts = input.split('examId=');
      if (parts.length > 1) {
        examId = parts[1].split('&')[0];
      }
    } else if (input.includes('#/exam/')) {
      const parts = input.split('#/exam/');
      if (parts.length > 1) {
        examId = parts[1];
      }
    }

    const match = allExams.find(
      (e) => e.id.toLowerCase() === examId.toLowerCase() || e.title.toLowerCase().includes(examId.toLowerCase())
    );

    if (match) {
      setCodeSuccess(`Exam "${match.title}" found! Opening exam room...`);
      setTimeout(() => {
        if (onSelectExam) {
          onSelectExam(match);
        }
      }, 1000);
    } else {
      setCodeError('Exam not found. Please double check the ID/URL code.');
    }
  };

  const subjects = [
    { name: 'Mathematics', icon: '📐', level: 'Primary & Secondary' },
    { name: 'English Language', icon: '✍', level: 'All Levels' },
    { name: 'Physics', icon: '⚡', level: 'Senior Secondary' },
    { name: 'Chemistry', icon: '🧪', level: 'Senior Secondary' },
    { name: 'Biology', icon: '🧬', level: 'Senior Secondary' },
    { name: 'Economics', icon: '📉', level: 'All Levels' },
    { name: 'Coding', icon: '💻', level: 'All Levels' },
    { name: 'Artificial Intelligence', icon: '🤖', level: 'All Levels' },
    { name: 'Cultural & Creative Arts', icon: '🎨', level: 'Primary & Secondary' },
    { name: 'Social Studies', icon: '🌍', level: 'Primary & Secondary' },
    { name: 'Physical & Health Education', icon: '⚽', level: 'All Levels' },
    { name: 'Phonics', icon: '🔤', level: 'Primary' },
  ];

  const features = [
    {
      title: 'Exam Question Generator',
      description: 'Generate comprehensive multiple-choice questions matching Nigeria and WAEC/NECO syllabus in seconds.',
      icon: <BrainCircuit className="w-5 h-5 text-violet-600" />,
    },
    {
      title: 'Lesson Note Generator',
      description: 'Input any subject or topic to instantly structure clear content notes, definitions, class exercises, and take-home tasks.',
      icon: <BookOpen className="w-5 h-5 text-emerald-600" />,
    },
    {
      title: 'CBT Exam Simulator',
      description: 'Host custom examinations with reliable countdown countdown limits, real-time logging, auto-submit bounds, and printable pass certificates.',
      icon: <GraduationCap className="w-5 h-5 text-indigo-600" />,
    },
    {
      title: 'Bulk CSV Question Upload',
      description: 'Supports teachers to upload hundreds of exam questions from spreadsheets instantly into active CBT packages.',
      icon: <School className="w-5 h-5 text-red-600" />,
    },
  ];

  return (
    <div className="bg-slate-55 text-slate-900 overflow-x-hidden">
      {/* Header Navigation */}
      <nav className="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200/80 shadow-xs">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-blue-950 via-red-600 to-emerald-600 text-white font-black text-lg shadow-lg shadow-blue-900/40">
               S
             </span>
            <span className="text-xl font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-950 via-red-600 to-emerald-650">
              Swiftstudy
            </span>
          </div>
          <div className="hidden md:flex items-center gap-8 text-sm font-bold text-slate-600">
            <a href="#features" className="hover:text-red-600 transition">Features</a>
            <a href="#subjects" className="hover:text-emerald-700 transition">Subjects</a>
            <a href="#pricing" className="hover:text-blue-950 transition">Pricing</a>
            <a href="#contact" className="hover:text-red-700 transition">Contact</a>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => onLoginClick('student')}
              className="px-5 py-2.5 text-xs font-black text-white bg-gradient-to-r from-blue-950 via-red-600 to-emerald-600 hover:from-blue-900 hover:to-emerald-750 rounded-xl shadow-md shadow-blue-900/20 transition cursor-pointer border-none"
            >
              Enter Portals
            </button>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="relative overflow-hidden pt-12 pb-20 sm:pt-16 sm:pb-28">
        <div className="absolute inset-0 bg-slate-100/40 -z-10" />
        <div className="absolute -top-40 right-10 w-96 h-96 bg-blue-900/10 rounded-full blur-3xl" />
        <div className="absolute top-20 -left-10 w-80 h-80 bg-red-600/5 rounded-full blur-3xl" />
        <div className="absolute bottom-10 right-2 w-80 h-80 bg-emerald-600/5 rounded-full blur-3xl" />

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-8">
          <motion.div
            initial={{ opacity: 0, y: 15 }}
            animate={{ opacity: 1, y: 0 }}
            className="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-gradient-to-r from-blue-50 to-red-50 border border-red-200 text-blue-950 rounded-full font-black text-xs shadow-sm"
          >
            <BookOpen className="w-3.5 h-3.5 text-red-600" />
            <span className="text-blue-950 font-extrabold">Complete CBT & Lesson Note Suite</span>
          </motion.div>

          <motion.h1
            initial={{ opacity: 0, y: 15 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="text-4xl sm:text-6xl font-black tracking-tight text-blue-950 max-w-4xl mx-auto leading-[1.1]"
          >
            Elevate School Excellence With{' '}
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-950 via-red-650 to-emerald-600">
              Dynamic Exam CBT & Lesson Organizers
            </span>
          </motion.h1>

          <motion.p
            initial={{ opacity: 0, y: 15 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.15 }}
            className="text-base sm:text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed font-semibold"
          >
            The definitive portal for custom lesson plans, class notes compilation, drag-and-drop CSV question adapters, and high-performance CBT exam taking for students.
          </motion.p>

          <motion.div
            initial={{ opacity: 0, y: 15 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="flex flex-wrap items-center justify-center gap-3"
          >
            <button
              onClick={onGetStarted}
              className="px-8 py-3.5 bg-gradient-to-r from-blue-950 via-red-600 to-emerald-600 hover:from-blue-900 hover:via-red-700 hover:to-emerald-700 text-white font-extrabold text-sm rounded-xl transition shadow-lg shadow-blue-950/20 cursor-pointer flex items-center gap-2 group border-none"
            >
              Get Started Freely
              <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
            </button>
            <button
              onClick={() => onLoginClick('student')}
              className="px-6 py-3.5 bg-emerald-50 text-emerald-800 border border-emerald-150 hover:bg-emerald-100 font-bold text-sm rounded-xl transition cursor-pointer"
            >
              Take CBT Exam
            </button>
          </motion.div>

          {/* Direct CBT Exam Entry Hall */}
          <motion.div
            initial={{ opacity: 0, y: 15 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.25 }}
            className="max-w-3xl mx-auto mt-12 p-6 sm:p-8 bg-gradient-to-tr from-blue-50/70 via-red-50/20 to-emerald-50/50 rounded-3xl border border-blue-150 shadow-xl text-left space-y-6"
          >
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div className="space-y-1">
                <div className="flex items-center gap-2">
                  <span className="flex h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse" />
                  <span className="text-[10px] uppercase font-black tracking-wider text-slate-500">Live CBT Examination Room</span>
                </div>
                <h3 className="text-lg font-black text-slate-900">Directly Join Exam Room</h3>
                <p className="text-xs text-slate-500">Paste your exam link, your exam code, or select an active test below to take it immediately.</p>
              </div>

              {/* Portal action designation */}
              <div className="shrink-0 flex gap-2">
                <span className="px-3.5 py-1.5 bg-white border border-slate-200 text-slate-600 rounded-xl text-[10px] font-bold">
                  ⚡ Code Entry Active
                </span>
              </div>
            </div>

            {/* Input code search form */}
            <form onSubmit={handleJoinByCode} className="space-y-3">
              <div className="flex flex-col sm:flex-row gap-2">
                <div className="relative flex-1 flex items-center">
                  <span className="absolute left-3.5 text-slate-400 z-10">
                    <KeyRound className="w-4 h-4 text-emerald-600" />
                  </span>
                  <input
                    type="text"
                    required
                    placeholder="Paste the exam link or enter exam code here (e.g. exam_xxxx)"
                    value={examCodeInput}
                    onChange={(e) => setExamCodeInput(e.target.value)}
                    className="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-10 py-3 text-xs font-semibold focus:outline-none focus:border-blue-900 focus:ring-1 focus:ring-blue-900"
                  />
                  <div className="absolute right-2 z-10 flex items-center">
                    <VoiceInputButton
                      value={examCodeInput}
                      onTranscript={(text) => setExamCodeInput(text)}
                      size="xs"
                    />
                  </div>
                </div>
                <button
                  type="submit"
                  className="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-md transition cursor-pointer shrink-0 uppercase tracking-wider"
                >
                  Enter Exam Room Now
                </button>
              </div>
              {codeError && <p className="text-xs text-rose-600 font-bold">⚠️ {codeError}</p>}
              {codeSuccess && <p className="text-xs text-emerald-600 font-bold">✓ {codeSuccess}</p>}
            </form>

            <div className="border-t border-slate-200/60 pt-4 space-y-3">
              <h4 className="text-xs font-extrabold text-slate-700 uppercase tracking-widest">Ongoing Published Exams Room ({activeExams.length})</h4>
              
              {loadingExams ? (
                <p className="text-xs text-slate-400 font-medium animate-pulse">Checking published exam registries...</p>
              ) : activeExams.length === 0 ? (
                <div className="p-4 bg-slate-100/55 rounded-xl text-center text-xs text-slate-400 font-medium">
                  No examinations have been published on this portal yet. Create and publish one as an educator to list it here!
                </div>
              ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-48 overflow-y-auto pr-1">
                  {activeExams.map((ex) => (
                    <div
                      key={ex.id}
                      className="p-3.5 bg-white hover:bg-blue-50/10 border border-slate-150 rounded-2xl flex flex-col justify-between gap-3 hover:border-blue-200 transition group"
                    >
                      <div className="space-y-1">
                        <div className="flex items-center justify-between">
                          <span className="text-[9px] bg-indigo-50 text-indigo-750 py-0.5 px-2 rounded-full font-bold">
                            {ex.subject}
                          </span>
                          <span className="text-[10px] text-slate-400 font-mono font-bold">⏱ {ex.duration} Mins</span>
                        </div>
                        <h5 className="text-xs font-black text-slate-800 line-clamp-1 group-hover:text-blue-900 transition">{ex.title}</h5>
                        <p className="text-[9px] text-slate-400 font-semibold uppercase">By {ex.creatorName}</p>
                      </div>

                      <button
                        onClick={() => {
                          if (onSelectExam) {
                            onSelectExam(ex);
                          }
                        }}
                        className="w-full py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white rounded-lg text-xs font-extrabold transition text-center cursor-pointer border border-emerald-100"
                      >
                        Start and Answer Questions ✨
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </motion.div>


          {/* Graphical Dashboard Teaser Mock */}
          <motion.div
            initial={{ opacity: 0, scale: 0.96 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ delay: 0.3 }}
            className="mt-16 relative mx-auto max-w-5xl rounded-3xl overflow-hidden shadow-2xl border border-slate-200"
          >
            <div className="bg-slate-900 text-white p-3 flex items-center gap-2 px-6">
              <span className="w-3 h-3 rounded-full bg-red-650 block" />
              <span className="w-3 h-3 rounded-full bg-emerald-500 block" />
              <span className="w-3 h-3 rounded-full bg-blue-900 block" />
              <span className="text-xs font-mono text-slate-400 ml-4 font-bold">Swiftstudy.Edu Dashboard Preview / CBT Arena</span>
            </div>
            <div className="p-8 sm:p-12 bg-white flex flex-col md:flex-row gap-8 items-center justify-between text-left">
              <div className="space-y-4 max-w-md">
                <span className="text-xs font-bold text-red-600 uppercase tracking-wider block">Interactive Suite</span>
                <h3 className="text-2xl font-extrabold text-blue-950 leading-tight">Nigeria's No. 1 Educator Portal for Primary & High Schools</h3>
                <p className="text-sm text-slate-600">
                  Fully operational dashboards configured with assessment engines, performance index lines, student scorecard tracking, and downloadable grade reports.
                </p>
                <div className="flex gap-4 pt-2">
                  <div className="text-center bg-slate-50 py-2 px-4 rounded-xl border border-slate-100">
                    <p className="text-xl font-bold text-slate-900">Free</p>
                    <p className="text-[10px] text-slate-400 font-bold">CBT Exam Publishes</p>
                  </div>
                  <div className="text-center bg-slate-50 py-2 px-4 rounded-xl border border-slate-100">
                    <p className="text-xl font-bold text-slate-900">100%</p>
                    <p className="text-[10px] text-slate-400 font-bold">Curriculum Aligned</p>
                  </div>
                </div>
              </div>
              <div className="w-full md:w-1/2 p-6 bg-gradient-to-br from-blue-50 to-emerald-50/50 rounded-2xl border border-blue-100 space-y-4 font-sans">
                <div className="flex items-center justify-between bg-white py-2.5 px-4 rounded-xl shadow-xs border border-blue-50">
                  <span className="text-xs font-bold text-slate-500">Active CBT Examinees</span>
                  <span className="text-xs font-bold text-emerald-600 font-extrabold">● 42 students online</span>
                </div>
                <div className="p-4 bg-white rounded-xl shadow-xs border border-blue-50 space-y-2 text-xs">
                  <div className="flex justify-between font-bold text-slate-700">
                    <span>Mathematics CBT</span>
                    <span className="text-emerald-700">78% Average Score</span>
                  </div>
                  <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                    <div className="bg-emerald-600 h-full w-4/5" />
                  </div>
                </div>
                <div className="p-4 bg-white rounded-xl shadow-xs border border-blue-50 space-y-2 text-xs">
                  <div className="flex justify-between font-bold text-slate-700">
                    <span>Physics Thermodynamics</span>
                    <span className="text-red-700 font-extrabold">88% Average Score</span>
                  </div>
                  <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                    <div className="bg-red-600 h-full w-[88%]" />
                  </div>
                </div>
              </div>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Features Grid Section */}
      <section id="features" className="py-20 bg-white border-t border-slate-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          <div className="text-center space-y-3">
            <h2 className="text-3xl font-black text-slate-900 tracking-tight">Tools Tailored for Modern Classrooms</h2>
            <p className="text-slate-600 max-w-xl mx-auto text-sm leading-relaxed">
              We leverage direct robust technologies to coordinate lesson notes, content delivery, and CBT test preparation.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {features.map((feat, index) => (
              <div key={index} className="p-6 bg-white hover:bg-slate-50 rounded-2xl border border-slate-200 hover:border-blue-900 transition duration-300 flex flex-col space-y-4">
                <span className="w-10 h-10 rounded-xl bg-slate-50 shadow-xs border border-slate-100 flex items-center justify-center">
                  {feat.icon}
                </span>
                <h3 className="text-base font-extrabold text-blue-950">{feat.title}</h3>
                <p className="text-xs text-slate-600 leading-relaxed flex-grow">{feat.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Available Subjects Shelf */}
      <section id="subjects" className="py-20 bg-slate-50 border-t border-slate-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          <div className="text-center space-y-3">
            <h2 className="text-3xl font-black text-slate-900 tracking-tight">Supporting All Subjects</h2>
            <p className="text-slate-600 max-w-xl mx-auto text-sm leading-relaxed">
              Designed to serve students from Primary, Junior Secondary to Senior Secondary School levels.
            </p>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            {subjects.map((sub, idx) => (
              <div key={idx} className="p-4 bg-white border border-slate-200/80 rounded-2xl text-center space-y-2 hover:shadow-md hover:border-emerald-500 transition duration-300">
                <span className="text-3xl block filter drop-shadow-sm">{sub.icon}</span>
                <p className="text-xs font-bold text-slate-900 leading-tight">{sub.name}</p>
                <p className="text-[10px] text-slate-400 font-bold tracking-wide uppercase">{sub.level}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Testimonials */}
      <section className="py-20 bg-white border-t border-slate-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          <div className="text-center space-y-3">
            <h2 className="text-3xl font-black text-blue-950 tracking-tight">Loved by Nigerian Educators</h2>
            <p className="text-slate-600 max-w-xl mx-auto text-sm">
              Read how schools are elevating digital assessment pipelines.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="p-6 bg-slate-50 rounded-2xl border border-slate-200 space-y-4">
              <div className="flex gap-1">
                {[...Array(5)].map((_, i) => <Star key={i} className="w-4 h-4 fill-amber-400 text-amber-400" />)}
              </div>
              <p className="text-xs text-slate-700 italic leading-relaxed">
                "Generating lesson notes used to take hours of manual copy pasting. With Swiftstudy, I generated a 3-week lesson note and evaluation questions in 2 minutes. The Nigerian syllabus alignment is spot-on!"
              </p>
              <div>
                <p className="text-xs font-bold text-slate-900">Mrs. Abigail Johnson</p>
                <p className="text-[10px] text-slate-400 font-semibold">Vice Principal, Swiftstudy Academy, Lagos</p>
              </div>
            </div>

            <div className="p-6 bg-slate-55 rounded-2xl border border-slate-200 space-y-4 animate-none">
              <div className="flex gap-1">
                {[...Array(5)].map((_, i) => <Star key={i} className="w-4 h-4 fill-amber-400 text-amber-400" />)}
              </div>
              <p className="text-xs text-slate-700 italic leading-relaxed">
                "The CSV upload is incredibly fast. I uploaded 150 questions in Chemistry and immediately got an invitation link for students. Scoring is instant!"
              </p>
              <div>
                <p className="text-xs font-bold text-slate-900">Mr. Austin Nwaigbo</p>
                <p className="text-[10px] text-slate-400 font-semibold">Chemistry Lecturer, JSS/SSS Tech</p>
              </div>
            </div>

            <div className="p-6 bg-slate-50 rounded-2xl border border-slate-200 space-y-4 animate-none">
              <div className="flex gap-1">
                {[...Array(5)].map((_, i) => <Star key={i} className="w-4 h-4 fill-amber-400 text-amber-400" />)}
              </div>
              <p className="text-xs text-slate-700 italic leading-relaxed">
                "Students took their trial CBT on mobile phones. Progress auto-saves, and the review Mode makes taking tests an educational experience. The downloadable performance certificate is beautiful!"
              </p>
              <div>
                <p className="text-xs font-bold text-slate-900">Rev. Dr. Peter Emmanuel</p>
                <p className="text-[10px] text-slate-400 font-semibold">Director, Landmark Schools</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Pricing matrix block */}
      <section id="pricing" className="py-20 bg-slate-50 border-t border-slate-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          <div className="text-center space-y-3">
            <h2 className="text-3xl font-black text-blue-950 tracking-tight">100% Free & Complimentary Access</h2>
            <p className="text-slate-600 max-w-xl mx-auto text-sm leading-relaxed">
              No subscription, no token fee. Our entire platform is now 100% free and open for teachers, students, and schools.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 max-w-5xl mx-auto gap-8">
            {/* Free Tier */}
            <div className="p-8 bg-white rounded-3xl border border-slate-200 relative space-y-6 flex flex-col justify-between">
              <div className="space-y-4">
                <span className="text-xs font-bold uppercase text-slate-400 tracking-wider">Student Tier</span>
                <h3 className="text-2xl font-black text-blue-955">Standard Practice CBT</h3>
                <p className="text-xs text-slate-500">Perfect for students starting practice or self-evaluation.</p>
                <div className="text-3xl font-black text-slate-900 font-sans">₦0 <span className="text-xs text-slate-400 font-medium">/ forever</span></div>
                <ul className="space-y-3 text-xs text-slate-600 font-semibold">
                  <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-500" /> Free Practice Questions Arena</li>
                  <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-500" /> Grade Reports & Fail Reviews</li>
                  <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-500" /> Custom CBT Exam Taking</li>
                  <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-500" /> Automated Lesson Notes Access</li>
                </ul>
              </div>
              <button
                onClick={onGetStarted}
                className="w-full py-2.5 px-4 bg-slate-150 hover:bg-slate-250 text-slate-800 font-bold rounded-xl text-xs transition transition duration-200 cursor-pointer"
              >
                Sign up Freely
              </button>
            </div>

            {/* Popular Tier */}
            <div className="p-8 bg-gradient-to-br from-blue-950 via-slate-900 to-emerald-950 text-white rounded-3xl relative space-y-6 flex flex-col justify-between shadow-xl ring-4 ring-emerald-500/10">
              <span className="absolute top-4 right-4 bg-gradient-to-r from-red-650 to-emerald-650 text-white font-extrabold text-[10px] uppercase py-1 px-3.5 rounded-full tracking-wider shadow-md font-sans">
                Swiftstudy Free
              </span>
              <div className="space-y-4">
                <span className="text-xs font-bold uppercase text-emerald-300 tracking-wider">Educator Bundle</span>
                <h3 className="text-2xl font-black text-white">Publish CBT Exams</h3>
                <p className="text-xs text-slate-300">Publish exams for hundreds of examinees, host link sessions.</p>
                <div className="text-4xl font-black text-white font-sans">₦1,000 <span className="text-xs text-emerald-300 font-medium">/ per CBT Published</span></div>
                <p className="text-[11px] text-slate-405 font-sans block">All features unlocked instantly & completely free</p>
                <ul className="space-y-3 text-xs text-slate-200 font-semibold">
                  <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> Bulk spreadsheet CSV Adapters</li>
                  <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> Secure live countdown timings</li>
                  <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> Auto-grade & Review modes</li>
                  <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> PDF certificates generation</li>
                  <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-400" /> Lesson plan tabular builder</li>
                </ul>
              </div>
              <button
                onClick={() => onLoginClick('student')}
                className="w-full py-3 px-4 bg-gradient-to-r from-red-650 to-emerald-600 hover:from-red-700 hover:to-emerald-700 text-white font-extrabold rounded-xl text-xs transition duration-205 cursor-pointer shadow-md shadow-red-900/10 border-none"
              >
                Access Portals Directly
              </button>
            </div>

            {/* Enterprise Tier */}
            <div className="p-8 bg-white rounded-3xl border border-slate-200 relative space-y-6 flex flex-col justify-between">
              <div className="space-y-4">
                <span className="text-xs font-bold uppercase text-slate-400 tracking-wider">Schools Suite</span>
                <h3 className="text-2xl font-black text-slate-900 font-sans">Full Enterprise</h3>
                <p className="text-xs text-slate-500">For large academic institutes, full customization, and branding.</p>
                <div className="text-3xl font-black text-slate-900 font-sans">Custom <span className="text-xs text-slate-400 font-medium font-sans">quote</span></div>
                <ul className="space-y-3 text-xs text-slate-600 font-semibold font-sans">
                  <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-500" /> Co-branded test headers</li>
                  <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-500" /> Advanced Instructor control desk</li>
                  <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-500" /> Dedicated database storage</li>
                  <li className="flex items-center gap-2"><Check className="w-4 h-4 text-emerald-500" /> 24/7 Priority support lines</li>
                </ul>
              </div>
              <button
                onClick={() => onLoginClick('student')}
                className="w-full py-2.5 px-4 bg-white text-slate-700 border border-slate-200 hover:bg-slate-55 font-bold rounded-xl text-xs transition duration-200 cursor-pointer border-none"
              >
                Access Portals Directly
              </button>
            </div>
          </div>
        </div>
      </section>

      {/* Contact Section */}
      <section id="contact" className="py-20 bg-white border-t border-slate-100">
        <div className="max-w-4xl mx-auto px-4 space-y-12">
          <div className="text-center space-y-3">
            <h2 className="text-3xl font-black text-slate-900 tracking-tight">Need Support or Have Questions?</h2>
            <p className="text-slate-600 text-sm">
              We are glad to help you modernize your school immediately. Reach out directly.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
            <div className="space-y-6 p-6 bg-slate-50 border border-slate-200 rounded-3xl">
              <h3 className="text-lg font-bold text-blue-950">Direct Contact details</h3>
              <div className="space-y-4 font-sans text-sm">
                <a
                  href="tel:08062078597"
                  className="flex items-center gap-3 p-2 rounded-2xl hover:bg-blue-100/40 transition group cursor-pointer block"
                >
                  <span className="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center text-blue-950 group-hover:scale-105 transition-transform shrink-0">
                    <Phone className="w-4 h-4" />
                  </span>
                  <div>
                    <p className="text-[11px] text-slate-400 font-bold uppercase">Phone line</p>
                    <p className="font-bold text-slate-800 group-hover:text-blue-950 transition">08062078597</p>
                  </div>
                </a>
                <a
                  href="https://wa.me/2348062078597?text=Hello%20Swiftstudy%20Educational%20Suite"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex items-center gap-3 p-2 rounded-2xl hover:bg-emerald-50 transition group cursor-pointer block"
                >
                  <span className="w-9 h-9 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 group-hover:scale-105 transition-transform shrink-0">
                    <MessageSquare className="w-4 h-4" />
                  </span>
                  <div>
                    <p className="text-[11px] text-slate-400 font-bold uppercase">WhatsApp link</p>
                    <p className="font-bold text-slate-800 group-hover:text-emerald-750 transition">08062078597</p>
                  </div>
                </a>
                <a
                  href="mailto:nwaigboaugust@gmail.com"
                  className="flex items-center gap-3 p-2 rounded-2xl hover:bg-red-50 transition group cursor-pointer block"
                >
                  <span className="w-9 h-9 bg-red-100 rounded-xl flex items-center justify-center text-red-600 group-hover:scale-105 transition-transform shrink-0">
                    <Shield className="w-4 h-4" />
                  </span>
                  <div>
                    <p className="text-[11px] text-slate-400 font-bold uppercase">Official Support Email</p>
                    <p className="font-bold text-slate-800 group-hover:text-red-700 transition">nwaigboaugust@gmail.com</p>
                  </div>
                </a>
              </div>
            </div>

            <form onSubmit={handleFeedbackSubmit} className="space-y-4">
              <div>
                <label className="text-xs font-semibold text-slate-700 block mb-1">Your Name</label>
                <div className="relative flex items-center">
                  <input
                    required
                    type="text"
                    value={feedbackName}
                    onChange={(e) => setFeedbackName(e.target.value)}
                    className="w-full bg-slate-55 border border-slate-200 rounded-xl py-2.5 pl-3 pr-10 text-sm focus:outline-none focus:border-blue-950 focus:bg-white transition"
                    placeholder="e.g. Augusta Johnson"
                  />
                  <div className="absolute right-2 z-10">
                    <VoiceInputButton
                      value={feedbackName}
                      onTranscript={(val) => setFeedbackName(val)}
                      size="xs"
                    />
                  </div>
                </div>
              </div>
              <div>
                <label className="text-xs font-semibold text-slate-700 block mb-1">School Email</label>
                <div className="relative flex items-center">
                  <input
                    required
                    type="email"
                    value={feedbackEmail}
                    onChange={(e) => setFeedbackEmail(e.target.value)}
                    className="w-full bg-slate-55 border border-slate-200 rounded-xl py-2.5 pl-3 pr-10 text-sm focus:outline-none focus:border-blue-950 focus:bg-white transition"
                    placeholder="scholar@mind.com"
                  />
                  <div className="absolute right-2 z-10">
                    <VoiceInputButton
                      value={feedbackEmail}
                      onTranscript={(val) => setFeedbackEmail(val)}
                      size="xs"
                    />
                  </div>
                </div>
              </div>
              <div>
                <label className="text-xs font-semibold text-slate-700 block mb-1">Inquiry Description</label>
                <div className="relative flex items-start">
                  <textarea
                    required
                    rows={3}
                    value={feedbackMsg}
                    onChange={(e) => setFeedbackMsg(e.target.value)}
                    className="w-full bg-slate-55 border border-slate-200 rounded-xl py-2.5 pl-3 pr-10 text-sm focus:outline-none focus:border-blue-950 focus:bg-white transition resize-none"
                    placeholder="How can we assist your educator team?"
                  />
                  <div className="absolute right-2 top-2 z-10">
                    <VoiceInputButton
                      value={feedbackMsg}
                      onTranscript={(val) => setFeedbackMsg(val)}
                      size="xs"
                    />
                  </div>
                </div>
              </div>

              {feedbackStatus === 'success' ? (
                <div className="bg-emerald-50 text-emerald-800 p-3 rounded-xl text-xs font-bold border border-emerald-100 flex items-center gap-2">
                  <Check className="w-4 h-4 text-emerald-600" />
                  Thank you! Your feedback support ticket has been securely logged on our system dashboard!
                </div>
              ) : feedbackStatus === 'error' ? (
                <div className="bg-rose-50 text-rose-800 p-3 rounded-xl text-xs font-bold border border-rose-100">
                  Failed to transmit your log. Please chat with us using the bot at the bottom right corner instead!
                </div>
              ) : (
                <button
                  type="submit"
                  disabled={feedbackStatus === 'loading'}
                  className="w-full py-3 bg-gradient-to-r from-blue-950 via-red-650 to-emerald-600 text-white font-extrabold text-xs rounded-xl shadow-md transition hover:opacity-95 cursor-pointer disabled:opacity-50 uppercase tracking-wider"
                >
                  {feedbackStatus === 'loading' ? 'Transmitting Log...' : 'Submit Help Inquiry'}
                </button>
              )}
            </form>
          </div>
        </div>
      </section>

      {/* Elegant Footer */}
      <footer className="bg-slate-905 text-white py-12 border-t border-slate-800 font-sans">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-8">
          <div className="space-y-4">
            <div className="flex items-center gap-2">
              <span className="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-950 text-white font-bold text-base">
                S
              </span>
              <span className="text-lg font-black tracking-tight">Swiftstudy</span>
            </div>
            <p className="text-xs text-slate-400 max-w-sm leading-relaxed">
              Nigeria's state-of-the-art educational interface transforming CBT examiners, bulk lesson planners, and student grading profiles.
            </p>
          </div>

          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-300">Quick Portal links</h4>
            <div className="flex flex-col gap-2 text-xs text-slate-400 font-semibold">
              <button onClick={() => onLoginClick('student')} className="text-left hover:text-white transition cursor-pointer border-none bg-transparent">Access Portals Directly</button>
            </div>
          </div>

          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-300">Direct Contact lines</h4>
            <a href="tel:08062078597" className="text-xs text-slate-400 hover:text-white transition font-semibold block">📞 Call: 08062078597</a>
            <a href="https://wa.me/2348062078597?text=Hello%20Swiftstudy%20Support" target="_blank" rel="noopener noreferrer" className="text-xs text-emerald-400 hover:text-emerald-300 transition font-semibold block">💬 WhatsApp: 08062078597</a>
            <a href="mailto:nwaigboaugust@gmail.com" className="text-xs text-slate-400 hover:text-white transition block font-semibold leading-relaxed">✉ Email: nwaigboaugust@gmail.com</a>
          </div>
        </div>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 border-t border-slate-800 mt-8 pt-8 text-center text-xs text-slate-500 font-semibold">
          <p>© {new Date().getFullYear()} Swiftstudy Education. All rights Reserved to authorized school districts.</p>
        </div>
      </footer>
    </div>
  );
}
