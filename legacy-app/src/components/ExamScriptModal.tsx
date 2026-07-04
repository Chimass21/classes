import { useState, useRef } from 'react';
import { 
  X, 
  Printer, 
  Download, 
  FileText, 
  Award, 
  Clock, 
  CheckCircle, 
  XSquare, 
  HelpCircle, 
  FileSignature, 
  TrendingUp, 
  Sparkles,
  Save,
  MessageSquarePlus
} from 'lucide-react';
import { jsPDF } from 'jspdf';

interface ExamScriptModalProps {
  result: any; // Result with candidate metadata & failedQuestions
  userRole?: 'student' | 'teacher' | 'admin';
  onClose: () => void;
  onUpdateRemarks?: (resultId: string, remarks: string, scoreOverride?: number) => void;
}

export default function ExamScriptModal({ result, userRole = 'student', onClose, onUpdateRemarks }: ExamScriptModalProps) {
  const [remarks, setRemarks] = useState(result.teacherRemarks || "");
  const [scoreOverride, setScoreOverride] = useState<number>(result.score ?? 0);
  const [isSavingRemarks, setIsSavingRemarks] = useState(false);
  const [remarksSavedSuccess, setRemarksSavedSuccess] = useState(false);
  const printContainerRef = useRef<HTMLDivElement>(null);

  const totalPossible = result.totalPossibleMarks || (result.totalQuestions * 5);
  const isPassed = result.percentage >= 50;

  const handleSaveRemarksAndScore = async () => {
    setIsSavingRemarks(true);
    setRemarksSavedSuccess(false);
    try {
      const response = await fetch(`/api/results/${result.id}/remarks`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          teacherRemarks: remarks,
          scoreOverride: scoreOverride
        })
      });
      if (response.ok) {
        setRemarksSavedSuccess(true);
        if (onUpdateRemarks) {
          onUpdateRemarks(result.id, remarks, scoreOverride);
        }
        setTimeout(() => setRemarksSavedSuccess(false), 3000);
      } else {
        alert("Failed to update script details.");
      }
    } catch (e) {
      console.error(e);
      alert("Error saving remarks.");
    } finally {
      setIsSavingRemarks(false);
    }
  };

  // 1. Download as PDF (using high-quality jsPDF text writer)
  const handleDownloadPDF = () => {
    const doc = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4'
    });

    const pageWidth = doc.internal.pageSize.getWidth();
    let y = 15;

    // Helper functions for layouts
    const addHeader = () => {
      doc.setFillColor(30, 41, 59); // dark slate Slate-900
      doc.rect(0, 0, pageWidth, 42, 'F');
      
      doc.setTextColor(255, 255, 255);
      doc.setFont('Helvetica', 'bold');
      doc.setFontSize(16);
      doc.text(result.schoolName || "WISDOM INTERNATIONAL ACADEMY", 15, 15);
      
      doc.setFont('Helvetica', 'normal');
      doc.setFontSize(9);
      doc.text("Enugu, Nigeria • Official CBT Academic Transcript Script", 15, 22);

      doc.setFillColor(245, 158, 11); // amber status accent line
      doc.rect(0, 27, pageWidth, 1.5, 'F');

      doc.setTextColor(255, 255, 255);
      doc.setFont('Helvetica', 'bold');
      doc.setFontSize(11);
      doc.text(`EXAMINATION REPORT SCRIPT: ${result.examTitle?.toUpperCase()}`, 15, 35);
      
      y = 48;
    };

    addHeader();

    // Student and Exam Details Card Grid
    doc.setFillColor(248, 250, 252); // soft slate border background
    doc.rect(12, y, pageWidth - 24, 45, 'F');
    doc.setDrawColor(226, 232, 240);
    doc.rect(12, y, pageWidth - 24, 45, 'D');

    doc.setTextColor(51, 65, 85);
    doc.setFont('Helvetica', 'bold');
    doc.setFontSize(10);
    doc.text("CANDIDATE AND STUDY TRANSCRIPT:", 16, y + 6);

    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(`Full Name:      ${result.studentName}`, 16, y + 14);
    doc.text(`Student ID:      ${result.studentRegNumber || result.studentId || "N/A"}`, 16, y + 21);
    doc.text(`Class Level:     ${result.studentClass || "Grade 10"}`, 16, y + 28);
    
    doc.text(`Subject Area:    ${result.subject}`, 110, y + 14);
    doc.text(`Exam Date:      ${new Date(result.date).toLocaleString()}`, 110, y + 21);
    doc.text(`Time Elapsed:   ${Math.round((result.timeSpent || 0) / 60)} minutes`, 110, y + 28);

    y += 52;

    // Score Performance Badge Box
    doc.setFillColor(239, 246, 255); // soft light blue
    doc.rect(12, y, pageWidth - 24, 25, 'F');
    doc.setDrawColor(191, 219, 254);
    doc.rect(12, y, pageWidth - 24, 25, 'D');

    doc.setTextColor(30, 58, 138);
    doc.setFont('Helvetica', 'bold');
    doc.setFontSize(11);
    doc.text(`SCORE: ${result.score} / ${totalPossible} Marks  (${result.percentage}%)`, 20, y + 15);
    doc.setFont('Helvetica', 'normal');
    const resultVerdict = result.percentage >= 50 ? "🥈 ACADEMIC STANDING: PASS" : "⚠️ ACADEMIC STANDING: FAILURE / RETAKE ASSIGNED";
    doc.text(resultVerdict, 105, y + 15);

    y += 32;

    // Teacher remarks
    if (result.teacherRemarks) {
      doc.setFillColor(254, 243, 199); // amber background for remarks
      doc.rect(12, y, pageWidth - 24, 18, 'F');
      doc.setDrawColor(251, 191, 36);
      doc.rect(12, y, pageWidth - 24, 18, 'D');
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(146, 64, 14);
      doc.setFontSize(9);
      doc.text("EDUCATOR REMARKS / INSTRUCTIONAL FEEDBACK:", 16, y + 6);
      doc.setFont('Helvetica', 'italic');
      doc.text(`"${result.teacherRemarks}"`, 16, y + 12);
      y += 24;
    }

    // Detailed question list breakdown
    doc.setFont('Helvetica', 'bold');
    doc.setTextColor(51, 65, 85);
    doc.setFontSize(10);
    doc.text("QUESTION-BY-QUESTION TRANSCRIPT BREAKDOWN:", 12, y);
    y += 6;

    const questions = result.failedQuestions || [];
    questions.forEach((q: any, idx: number) => {
      // Check for page break
      if (y > 250) {
        doc.addPage();
        y = 15;
        doc.setFillColor(30, 41, 59);
        doc.rect(0, 0, pageWidth, 10, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(8);
        doc.text(`Page 2 of 2 - Exam Script Transcript for ${result.studentName}`, 15, 7);
        y = 20;
      }

      doc.setDrawColor(241, 245, 249);
      doc.line(12, y, pageWidth - 12, y);
      y += 5;

      doc.setTextColor(15, 23, 42);
      doc.setFont('Helvetica', 'bold');
      doc.setFontSize(10);
      doc.text(`Q${idx + 1}.`, 12, y);
      
      // Question Text wrapping to fit page cleanly
      doc.setFont('Helvetica', 'normal');
      doc.setFontSize(9);
      const wrappedQ = doc.splitTextToSize(q.question, pageWidth - 30);
      doc.text(wrappedQ, 20, y);
      y += (wrappedQ.length * 4.5);

      // Selected vs Correct answer details
      doc.setFont('Helvetica', 'normal');
      doc.setFontSize(8.5);

      if (q.type === 'theory') {
        doc.setTextColor(100, 116, 139);
        doc.text("Student Written Theory Answer:", 20, y);
        y += 4.5;
        doc.setTextColor(51, 65, 85);
        doc.setFont('Helvetica', 'italic');
        const wrappedAnswer = doc.splitTextToSize(q.selectedAnswer || "[No answer submitted]", pageWidth - 35);
        doc.text(wrappedAnswer, 22, y);
        y += (wrappedAnswer.length * 4);

        doc.setTextColor(16, 185, 129); // green for correct scheme
        doc.setFont('Helvetica', 'bold');
        doc.text("Standard Correct Response / Marking Key Guide:", 20, y);
        y += 4.5;
        doc.setFont('Helvetica', 'normal');
        const wrappedKey = doc.splitTextToSize(q.correctAnswer || "Refer to teacher reference note.", pageWidth - 35);
        doc.text(wrappedKey, 22, y);
        y += (wrappedKey.length * 4) + 2;
      } else {
        // Objective Choice display
        doc.setTextColor(71, 85, 105);
        doc.text(`A: ${q.optionA || 'N/A'}    B: ${q.optionB || 'N/A'}    C: ${q.optionC || 'N/A'}    D: ${q.optionD || 'N/A'}`, 20, y);
        y += 5;

        // Highlight candidate response with colored banner tags in text
        if (q.isCorrect) {
          doc.setTextColor(16, 185, 129); // emerald green
          doc.setFont('Helvetica', 'bold');
          doc.text(`Correct! Candidate Choice: Option ${q.selectedAnswer || 'N/A'}  •  Awarded Marks: ${q.marksAwarded || q.marks || 5} / ${q.marks || 5}`, 20, y);
        } else {
          doc.setTextColor(239, 68, 68); // light red
          doc.setFont('Helvetica', 'bold');
          doc.text(`Incorrect. Candidate Choice: Option ${q.selectedAnswer || '[None]'} (Correct: Option ${q.correctAnswer}) • Marks: 0 / ${q.marks || 5}`, 20, y);
        }
        y += 5.5;
      }

      doc.setTextColor(100, 116, 139);
      doc.setFont('Helvetica', 'normal');
      doc.setFontSize(8);
      doc.text(`Topic domain: ${q.topic || 'General concept'}`, 20, y);

      y += 8;
    });

    // Save script report file
    doc.save(`Exam_Script_${result.studentName?.replace(/\s+/g, '_')}_${result.subject?.replace(/\s+/g, '_')}.pdf`);
  };

  // 2. Download as Microsoft Word .docx
  const handleDownloadDOCX = () => {
    const tableHeaderStyle = "background-color: #1e3a8a; color: #ffffff; padding: 10px; font-weight: bold;";
    const scoreBoxStyle = isPassed ? "background-color: #ecfdf5; border-left: 5px solid #10b981; padding: 12px; margin: 15px 0;" : "background-color: #fff1f2; border-left: 5px solid #f43f5e; padding: 12px; margin: 15px 0;";
    
    let questionsHtml = "";
    const questionsList = result.failedQuestions || [];
    questionsList.forEach((q: any, idx: number) => {
      const isCorrectText = q.isCorrect ? "CORRECT" : "INCORRECT";
      let answerSegment = "";

      if (q.type === 'theory') {
        answerSegment = `
          <p style="margin: 4px 0 8px 0;"><strong>Candidate Theory Response:</strong><br/>
          <em style="color: #475569; display: block; padding-left: 10px; border-left: 2px solid #cbd5e1;">${q.selectedAnswer || '[No submission]'}</em></p>
          <p style="margin: 4px 0;"><strong>Standard Scoring Guidelines:</strong><br/>
          <span style="color: #15803d; font-size: 10pt;">${q.correctAnswer || 'Refer to curriculum guidelines.'}</span></p>
        `;
      } else {
        answerSegment = `
          <ul style="margin: 4px 0 8px 0; padding-left: 20px; list-style-type: square; color: #475569;">
            <li><strong>Option A:</strong> ${q.optionA || ""}</li>
            <li><strong>Option B:</strong> ${q.optionB || ""}</li>
            <li><strong>Option C:</strong> ${q.optionC || ""}</li>
            <li><strong>Option D:</strong> ${q.optionD || ""}</li>
          </ul>
          <p style="margin: 4px 0;"><strong>Candidate Choice Answer Selection:</strong> Option ${q.selectedAnswer || '[No choice selected]'} (${isCorrectText})</p>
          <p style="margin: 4px 0;"><strong>Expected Correct Option Key:</strong> Option ${q.correctAnswer}</p>
        `;
      }

      questionsHtml += `
        <div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
          <h4 style="color: #334155; margin-bottom: 5px;">Question ${idx + 1} (${q.type === 'theory' ? 'Theory Question' : 'Objective MCQ'})</h4>
          <p style="font-size: 11pt; margin-bottom: 10px; background-color: #f8fafc; padding: 8px; border-radius: 4px;">${q.question}</p>
          ${answerSegment}
          <p style="margin-top: 6px; font-size: 9pt; color: #64748b;">Marks Awarded: ${q.marksAwarded ?? (q.isCorrect ? q.marks : 0)} / ${q.marks || 5} • Concept topic: ${q.topic || "General"}</p>
        </div>
      `;
    });

    const docxHtml = `
      <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
      <head>
        <meta charset="utf-8">
        <title>Examination Script Transcript</title>
        <style>
          body { font-family: 'Calibri', 'Arial', sans-serif; line-height: 1.4; color: #334155; }
          .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
          .title { font-size: 18pt; font-weight: bold; color: #1e3a8a; }
          .subtitle { font-size: 10pt; color: #64748b; }
          .details-table { width: 100%; border: 1px solid #cbd5e1; border-collapse: collapse; margin: 15px 0; }
          .details-table td { border: 1px solid #cbd5e1; padding: 8px; font-size: 10pt; }
          .section-title { color: #1e3a8a; font-size: 13pt; font-weight: bold; border-bottom: 2px solid #3b82f6; padding-bottom: 5px; margin-top: 25px; }
        </style>
      </head>
      <body>
        <table class="header-table">
          <tr>
            <td>
              <div class="title">${result.schoolName || "WISDOM INTERNATIONAL ACADEMY"}</div>
              <div class="subtitle">Official CBT Assessment Record & Evaluation Transcript</div>
            </td>
          </tr>
        </table>

        <div class="section-title">EXAMINATION OVERVIEW</div>
        <table class="details-table">
          <tr>
            <td style="font-weight:bold; background-color:#f1f5f9;">Candidate Name</td>
            <td>${result.studentName}</td>
            <td style="font-weight:bold; background-color:#f1f5f9;">Subject Area</td>
            <td>${result.subject}</td>
          </tr>
          <tr>
            <td style="font-weight:bold; background-color:#f1f5f9;">Student ID / RegNo</td>
            <td>${result.studentRegNumber || result.studentId || "N/A"}</td>
            <td style="font-weight:bold; background-color:#f1f5f9;">Assessment Title</td>
            <td>${result.examTitle}</td>
          </tr>
          <tr>
            <td style="font-weight:bold; background-color:#f1f5f9;">Grade Class</td>
            <td>${result.studentClass || "Grade 10"}</td>
            <td style="font-weight:bold; background-color:#f1f5f9;">Submitting Date</td>
            <td>${new Date(result.date).toLocaleString()}</td>
          </tr>
          <tr>
            <td style="font-weight:bold; background-color:#f1f5f9;">Time Spent</td>
            <td>${Math.round((result.timeSpent || 0) / 60)} minutes</td>
            <td style="font-weight:bold; background-color:#f1f5f9;">Assigned standing</td>
            <td style="font-weight:bold; color: ${isPassed ? '#10b981' : '#f43f5e'};">${isPassed ? 'Passed' : 'Needs Study Drills / Retake'}</td>
          </tr>
        </table>

        <div class="${scoreBoxStyle}">
          <h3 style="margin: 0 0 5px 0;">Final Score: ${result.score} / ${totalPossible} Marks</h3>
          <p style="margin: 0; font-size: 11pt;">Performance Percentage Grade: <strong>${result.percentage}%</strong></p>
        </div>

        ${result.teacherRemarks ? `
        <div style="background-color: #fef3c7; border-left: 5px solid #f59e0b; padding: 10px; margin: 15px 0;">
          <h4 style="color: #b45309; margin: 0 0 4px 0;">Teacher Feedback remarks</h4>
          <p style="margin: 0; font-style: italic; font-size: 10pt;">"${result.teacherRemarks}"</p>
        </div>
        ` : ""}

        <div class="section-title">QUESTION-BY-QUESTION SCRIPT BREAKDOWN</div>
        ${questionsHtml}

        <p style="text-align: center; font-size: 8pt; color: #94a3b8; margin-top: 40px; border-top: 1px dashed #cbd5e1; padding-top: 10px;">
          Generated automatically from CBT Software Examination Database on ${new Date().toLocaleDateString()}. Page 1 of 1.
        </p>
      </body>
      </html>
    `;

    const blob = new Blob(['\ufeff' + docxHtml], { type: 'application/msword' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `Exam_Script_${result.studentName?.replace(/\s+/g, '_')}_${result.subject?.replace(/\s+/g, '_')}.doc`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // 3. Print exam script directly from current browser using clean print media layout styling
  const handlePrintScript = () => {
    window.print();
  };

  return (
    <div id="exam-script-modal" className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/65 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 font-sans antialiased">
      
      {/* Print Specific Inline Styling to clean results on paper */}
      <style>{`
        @media print {
          body * {
            visibility: hidden;
          }
          #print-area, #print-area * {
            visibility: visible;
          }
          #print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            background-color: white !important;
            color: black !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
          }
          #exam-script-modal {
            background-color: transparent !important;
            position: static !important;
            display: block !important;
          }
          .no-print {
            display: none !important;
          }
        }
      `}</style>

      {/* Main modal container */}
      <div className="bg-slate-50 w-full max-w-4xl max-h-[90vh] rounded-3xl overflow-hidden shadow-2xl border border-slate-200 flex flex-col transform transition duration-300">
        
        {/* Modal Top Control Bar */}
        <div className="no-print bg-white px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shrink-0">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
              <FileText className="w-4 h-4" />
            </div>
            <div>
              <h3 className="text-sm font-black text-slate-900">Academic CBT Script Transcripts</h3>
              <p className="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Inspect answers, download, or review scripts</p>
            </div>
          </div>

          <div className="flex items-center gap-2 self-end sm:self-auto">
            <button
              type="button"
              onClick={handleDownloadPDF}
              className="bg-red-50 hover:bg-red-100 text-red-600 font-bold text-xs py-2 px-3.5 rounded-xl border border-red-100 flex items-center gap-1.5 transition cursor-pointer"
            >
              <Download className="w-4 h-4" />
              PDF
            </button>
            <button
              type="button"
              onClick={handleDownloadDOCX}
              className="bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold text-xs py-2 px-3.5 rounded-xl border border-blue-100 flex items-center gap-1.5 transition cursor-pointer"
            >
              <FileText className="w-4 h-4" />
              Word DOC
            </button>
            <button
              type="button"
              onClick={handlePrintScript}
              className="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-2 px-3.5 rounded-xl border border-slate-200 flex items-center gap-1.5 transition cursor-pointer"
            >
              <Printer className="w-4 h-4" />
              Print
            </button>
            <button
              type="button"
              onClick={onClose}
              className="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition cursor-pointer ml-2 border-none"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        {/* Scrollable contents area */}
        <div className="overflow-y-auto flex-1 p-6 space-y-6">
          
          {/* Print specific container with strict professional styling */}
          <div ref={printContainerRef} id="print-area" className="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
            
            {/* School Logo Title banner */}
            <div className="flex items-start justify-between pb-6 border-b-2 border-slate-900">
              <div className="flex items-center gap-4">
                <img 
                  src={result.schoolLogo || "https://api.dicebear.com/7.x/identicon/svg?seed=wisdom"} 
                  alt="school logo" 
                  className="w-16 h-16 rounded-xl border-2 border-indigo-650 object-cover"
                  referrerPolicy="no-referrer"
                />
                <div>
                  <h1 className="text-lg font-black text-slate-950 uppercase tracking-tight">{result.schoolName || "WISDOM INTERNATIONAL ACADEMY"}</h1>
                  <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Enugu, Nigeria • Official Evaluation Records</p>
                  <p className="text-xs text-indigo-650 font-bold mt-1">Computer Based Testing (CBT) Script Transcript</p>
                </div>
              </div>

              <div className="text-right">
                <div className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-extrabold ${isPassed ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'}`}>
                  {isPassed ? "🥈 PASSED" : "⚠️ UNPASSED"}
                </div>
                <p className="text-[10px] text-slate-400 font-bold mt-2">TRANSCRIPT ID: <span className="font-mono text-slate-700 text-xs">{result.id}</span></p>
              </div>
            </div>

            {/* Candidate Identity Registry Card Grid */}
            <div className="bg-slate-50 border border-slate-200 rounded-2xl p-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
              <div className="space-y-2">
                <p className="text-slate-400 font-bold uppercase tracking-wider text-[9px]">Student Candidate details</p>
                <div className="space-y-1 text-slate-800 font-semibold">
                  <p>Full Name: <strong className="text-slate-950 font-black">{result.studentName}</strong></p>
                  <p>Student ID: <strong className="text-slate-950 font-black">{result.studentRegNumber || result.studentId || "N/A"}</strong></p>
                  <p>Class Level: <strong className="text-slate-950 font-black">{result.studentClass || "Grade 10"}</strong></p>
                </div>
              </div>

              <div className="space-y-2">
                <p className="text-slate-400 font-bold uppercase tracking-wider text-[9px]">Examination Details</p>
                <div className="space-y-1 text-slate-800 font-semibold">
                  <p>Subject Area: <strong className="text-slate-950 font-black">{result.subject}</strong></p>
                  <p>Examination: <strong className="text-slate-950 font-black">{result.examTitle}</strong></p>
                  <p>Completed Date: <strong className="text-slate-950 font-black">{new Date(result.date).toLocaleString()}</strong></p>
                </div>
              </div>
            </div>

            {/* Assessment Score highlights panel */}
            <div className={`p-6 rounded-2xl grid grid-cols-1 sm:grid-cols-4 gap-4 ${isPassed ? 'bg-emerald-50/50 border border-emerald-100' : 'bg-rose-50/50 border border-rose-100'}`}>
              <div className="text-center sm:text-left">
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Score Obtained</span>
                <strong className="text-2xl font-black text-slate-900">{result.score}</strong>
                <span className="text-slate-400 text-xs"> / {totalPossible} Marks</span>
              </div>

              <div className="text-center sm:text-left">
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Percentage Grade</span>
                <strong className={`text-2xl font-black ${isPassed ? 'text-emerald-700' : 'text-rose-600'}`}>{result.percentage}%</strong>
              </div>

              <div className="text-center sm:text-left">
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Time Elapsed</span>
                <div className="flex items-center justify-center sm:justify-start gap-1 mt-1 text-slate-700 font-bold">
                  <Clock className="w-4 h-4 text-slate-400" />
                  <span>{Math.round((result.timeSpent || 0) / 60)} mins</span>
                </div>
              </div>

              <div className="text-center sm:text-left">
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Correct Questions</span>
                <div className="flex items-center justify-center sm:justify-start gap-1 mt-1 text-slate-700 font-bold">
                  <CheckCircle className="w-4 h-4 text-emerald-500" />
                  <span>{result.correctAnswers} / {result.totalQuestions || 0} Items</span>
                </div>
              </div>
            </div>

            {/* Educator comment section */}
            <div className="no-print bg-amber-500/5 border border-amber-300 rounded-2xl p-5 space-y-3">
              <div className="flex items-center gap-1.5 text-amber-800">
                <FileSignature className="w-4 h-4" />
                <h4 className="text-xs font-black uppercase tracking-wider">Educator Grading Commentary Panel</h4>
              </div>
              
              <p className="text-xs text-amber-900/80 leading-relaxed font-semibold">
                Educator remarks provide customized academic feedback, guidelines, correct indicators, can be updated at any moment.
              </p>

              {userRole === 'teacher' || userRole === 'admin' ? (
                <div className="space-y-3 pt-1">
                  <div>
                    <label className="text-[10px] text-slate-400 font-bold block mb-1">Teacher's Remark</label>
                    <textarea
                      rows={2}
                      className="w-full bg-white border border-slate-200 rounded-xl p-3 text-xs font-medium focus:outline-none"
                      placeholder="Enter specific student corrections context..."
                      value={remarks}
                      onChange={(e) => setRemarks(e.target.value)}
                    />
                  </div>

                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                      <span className="text-[10px] text-slate-400 font-bold">Marks Override (Optional):</span>
                      <input
                        type="number"
                        className="bg-white border border-slate-200 rounded-lg p-1.5 text-xs font-bold w-20 focus:outline-none"
                        value={scoreOverride}
                        onChange={(e) => setScoreOverride(Number(e.target.value))}
                      />
                    </div>
                    <button
                      type="button"
                      disabled={isSavingRemarks}
                      onClick={handleSaveRemarksAndScore}
                      className="bg-amber-500 hover:bg-amber-600 font-bold text-xs py-2 px-4 rounded-xl text-slate-950 flex items-center justify-center gap-1 cursor-pointer"
                    >
                      <Save className="w-4 h-4" />
                      {isSavingRemarks ? "Saving..." : "Save Remarks & Override"}
                    </button>
                  </div>
                  {remarksSavedSuccess && (
                    <p className="text-xs font-bold text-emerald-600 animate-fadeIn">✓ Remarks and overridden scores successfully updated!</p>
                  )}
                </div>
              ) : (
                <div className="p-3 bg-white border border-amber-100/50 rounded-xl text-xs text-amber-950">
                  {result.teacherRemarks ? (
                    <p className="font-semibold italic">"{result.teacherRemarks}"</p>
                  ) : (
                    <p className="text-slate-400 italic font-medium">Pending teacher comments.</p>
                  )}
                </div>
              )}
            </div>

            {/* Detailed Question breakdown item logs */}
            <div className="space-y-4 pt-4">
              <h3 className="text-xs font-black uppercase tracking-widest text-slate-500 border-b pb-2">Question-by-Question Script Transcript</h3>
              
              <div className="space-y-4">
                {(result.failedQuestions || []).map((q: any, qIndex: number) => {
                  return (
                    <div key={qIndex} className="p-5 border border-slate-150 rounded-2xl bg-white hover:shadow-xs transition space-y-3 text-xs">
                      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2">
                        <span className="font-bold text-slate-500">
                          Question {qIndex + 1} • <span className="uppercase text-[10px] bg-slate-100 text-slate-700 py-0.5 px-2 rounded-full font-bold">{q.type || 'objective'}</span>
                        </span>
                        
                        <div className="flex items-center gap-1.5 font-bold">
                          {q.isCorrect ? (
                            <span className="text-emerald-600 flex items-center gap-0.5"><CheckCircle className="w-3.5 h-3.5" /> Correct</span>
                          ) : (
                            <span className="text-rose-500 flex items-center gap-0.5"><XSquare className="w-3.5 h-3.5" /> Incorrect</span>
                          )}
                          <span className="text-slate-400 text-[10px]">({q.marksAwarded ?? (q.isCorrect ? q.marks : 0)} / {q.marks || 5} marks)</span>
                        </div>
                      </div>

                      <p className="font-bold text-slate-900 leading-relaxed bg-slate-50/50 p-3 rounded-lg border border-slate-100 break-words">{q.question}</p>

                      {q.type === 'theory' ? (
                        <div className="space-y-3 pt-1">
                          <div>
                            <span className="text-[10px] text-slate-400 font-bold block mb-1">Candidate Theory Answer:</span>
                            <div className="p-3 bg-slate-100 text-slate-700 rounded-xl border border-slate-200 select-all leading-normal whitespace-pre-line font-medium italic break-words">
                              {q.selectedAnswer || '[No response submitted]'}
                            </div>
                          </div>

                          <div>
                            <span className="text-[10px] text-slate-400 font-bold block mb-1">Expected Standard Grading Answer Schemes / Target Content:</span>
                            <div className="p-3 bg-emerald-500/5 text-emerald-800 rounded-xl border border-emerald-100 leading-normal break-words">
                              {q.correctAnswer}
                            </div>
                          </div>
                        </div>
                      ) : (
                        <div className="space-y-2.5">
                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-600 font-semibold">
                          {[
                            { key: 'A', label: q.optionA },
                            { key: 'B', label: q.optionB },
                            { key: 'C', label: q.optionC },
                            { key: 'D', label: q.optionD },
                          ].map((opt) => {
                            const isCorrectOpt = opt.key === q.correctAnswer;
                            const isSelectedOpt = opt.key === q.selectedAnswer;
                            let borderCls = 'border-slate-100';
                            let bgCls = 'bg-slate-50';
                            let textCls = 'text-slate-700';
                            if (isCorrectOpt) { borderCls = 'border-emerald-300'; bgCls = 'bg-emerald-50'; textCls = 'text-emerald-800'; }
                            else if (isSelectedOpt) { borderCls = 'border-rose-300'; bgCls = 'bg-rose-50'; textCls = 'text-rose-800'; }
                            return (
                              <p key={opt.key} className={`p-2.5 rounded-lg border break-words ${borderCls} ${bgCls} ${textCls}`}>
                                <span className="font-bold">Option {opt.key}:</span> {opt.label || 'N/A'}
                              </p>
                            );
                          })}
                          </div>

                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 text-[11px]">
                            <div className="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                              <span className="text-slate-400 font-bold block mb-0.5">Candidate Choice</span>
                              <strong className={q.isCorrect ? "text-emerald-700 font-black break-words" : "text-rose-600 font-black break-words"}>Option {q.selectedAnswer || '[No answer]'}</strong>
                            </div>
                            <div className="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                              <span className="text-slate-400 font-bold block mb-0.5">Expected Correct</span>
                              <strong className="text-emerald-750 font-black break-words">Option {q.correctAnswer}</strong>
                            </div>
                          </div>
                        </div>
                      )}

                      {q.explanation && (
                        <details className="text-[11px] bg-indigo-50/20 text-indigo-950 p-2.5 rounded-xl border border-indigo-100/50 cursor-pointer">
                          <summary className="font-bold focus:outline-none">View concept explanation & reference note</summary>
                          <p className="mt-2 text-slate-600 leading-relaxed font-semibold cursor-auto select-text">{q.explanation}</p>
                        </details>
                      )}
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Report footer section info tags */}
            <div className="text-center pt-8 border-t border-slate-200 text-[10px] text-slate-400 space-y-1">
              <p className="font-semibold uppercase tracking-widest text-slate-500">WISDOM INTERNATIONAL ACADEMY COMPUTER BASED TESTING TRANSCRIPTION</p>
              <p className="font-medium">Evaluated and processed on {new Date(result.date || Date.now()).toLocaleDateString()} at {new Date(result.date || Date.now()).toLocaleTimeString()}</p>
              <p className="text-[9px]">Confidential Transcript • Copyright © {new Date().getFullYear()} School Core CBT Engine. Page 1 of 1</p>
            </div>

          </div>

        </div>

      </div>

    </div>
  );
}
