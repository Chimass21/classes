import React, { useState, useEffect, useRef } from 'react';
import { Play, Pause, Square, Volume2, Gauge, RefreshCw, UserRound, Award } from 'lucide-react';

interface CBTVoiceReaderProps {
  question: string;
  optionA: string;
  optionB: string;
  optionC: string;
  optionD: string;
  accentColor?: string;
}

export default function CBTVoiceReader({
  question,
  optionA,
  optionB,
  optionC,
  optionD,
  accentColor = "indigo"
}: CBTVoiceReaderProps) {
  const [isPlaying, setIsPlaying] = useState(false);
  const [isPaused, setIsPaused] = useState(false);
  const [volume, setVolume] = useState(1.0);
  const [speed, setSpeed] = useState(1.0);
  const [genderFilter, setGenderFilter] = useState<'female' | 'male'>('female');
  const [availableVoices, setAvailableVoices] = useState<SpeechSynthesisVoice[]>([]);
  const [showSettings, setShowSettings] = useState(false);
  const utteranceRef = useRef<SpeechSynthesisUtterance | null>(null);

  // Load available voices on mount and voice change events
  useEffect(() => {
    if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
      const loadVoices = () => {
        const voices = window.speechSynthesis.getVoices();
        setAvailableVoices(voices);
      };
      loadVoices();
      window.speechSynthesis.onvoiceschanged = loadVoices;
    }
  }, []);

  // Clean up Speech on page change or component unmount
  useEffect(() => {
    return () => {
      if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
        window.speechSynthesis.cancel();
      }
    };
  }, []);

  // Whenever the question or options change, let's stop current reading to be in sync
  useEffect(() => {
    handleStop();
  }, [question, optionA, optionB, optionC, optionD]);

  // Clean math markers and HTML tags before reading aloud
  const cleanSpeechText = (rawText: string) => {
    const div = document.createElement("div");
    div.innerHTML = rawText;
    let text = div.innerText || div.textContent || "";
    
    // Process math symbols for descriptive reading
    return text
      .replace(/\\frac\{([^}]+)\}\{([^}]+)\}/g, "$1 over $2")
      .replace(/\^\{([^}]+)\}/g, " raised to power $1")
      .replace(/\_\{([^}]+)\}/g, " subscript $1")
      .replace(/[\$\\]/g, " ")
      .trim();
  };

  // Find a voice based on gender selection
  const selectVoiceForGender = (gender: 'female' | 'male') => {
    const systemVoices = availableVoices.length > 0 ? availableVoices : window.speechSynthesis.getVoices();
    // Default fallback voice
    const englishVoices = systemVoices.filter(v => v.lang.startsWith('en-') || v.lang.startsWith('en_') || v.lang.startsWith('en'));
    
    // Simple classification keywords
    const femaleKeywords = ["zira", "samantha", "susan", "hazel", "karen", "moira", "tessa", "veena", "fiona", "female", "google us english", "microsoft zira"];
    const maleKeywords = ["david", "george", "ravi", "mark", "danny", "stefan", "male", "google uk english male", "microsoft david"];

    if (gender === 'female') {
      const match = englishVoices.find(v => femaleKeywords.some(kw => v.name.toLowerCase().includes(kw)));
      if (match) return match;
    } else {
      const match = englishVoices.find(v => maleKeywords.some(kw => v.name.toLowerCase().includes(kw)));
      if (match) return match;
    }

    // Default to any English voice
    return englishVoices[0] || systemVoices[0] || null;
  };

  const handlePlay = () => {
    if (typeof window === 'undefined' || !('speechSynthesis' in window)) {
      alert("TTS not supported in this browser perspective.");
      return;
    }

    // Cancel dynamic speaking beforehand
    window.speechSynthesis.cancel();

    const qText = cleanSpeechText(question);
    const optA = cleanSpeechText(optionA);
    const optB = cleanSpeechText(optionB);
    const optC = cleanSpeechText(optionC);
    const optD = cleanSpeechText(optionD);

    const fullNarrative = `Question. ${qText}. Option A. ${optA}. Option B. ${optB}. Option C. ${optC}. Option D. ${optD}.`;

    const utterance = new SpeechSynthesisUtterance(fullNarrative);
    utterance.volume = volume;
    utterance.rate = speed;
    
    const chosenVoice = selectVoiceForGender(genderFilter);
    if (chosenVoice) {
      utterance.voice = chosenVoice;
    }

    utterance.onstart = () => {
      setIsPlaying(true);
      setIsPaused(false);
    };

    utterance.onend = () => {
      setIsPlaying(false);
      setIsPaused(false);
    };

    utterance.onerror = (e) => {
      console.warn("Speech synthesis trigger finished or stopped:", e);
      setIsPlaying(false);
      setIsPaused(false);
    };

    utteranceRef.current = utterance;
    window.speechSynthesis.speak(utterance);
  };

  const handlePause = () => {
    if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
      if (window.speechSynthesis.speaking && !window.speechSynthesis.paused) {
        window.speechSynthesis.pause();
        setIsPaused(true);
      }
    }
  };

  const handleResume = () => {
    if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
      if (window.speechSynthesis.paused) {
        window.speechSynthesis.resume();
        setIsPaused(false);
      } else {
        // If it got cancelled somehow under iOS, just play again
        handlePlay();
      }
    }
  };

  const handleStop = () => {
    if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
      window.speechSynthesis.cancel();
      setIsPlaying(false);
      setIsPaused(false);
    }
  };

  // Re-initiate voice speaking if settings volume/rate/gender change dynamically during speech
  const updateSpeechLive = () => {
    if (isPlaying) {
      handlePlay();
    }
  };

  const textStyle = accentColor === "indigo" ? "text-indigo-600" : accentColor === "teal" ? "text-teal-600" : "text-violet-600";
  const bgStyle = accentColor === "indigo" ? "bg-indigo-50/70 border-indigo-100/85 text-indigo-900" : accentColor === "teal" ? "bg-teal-50/70 border-teal-100/85 text-teal-900" : "bg-violet-50/70 text-violet-900 border-violet-150/85";

  return (
    <div className={`p-1.5 px-2.5 rounded-xl border ${bgStyle} font-sans w-full max-w-xl mx-auto shadow-xs text-slate-800 transition-all duration-300`}>
      {/* Horizontal Toolbar Container */}
      <div className="flex items-center justify-between flex-wrap gap-2 text-xs">
        {/* Left: Indicator & Speaker Badge */}
        <div className="flex items-center gap-1.5">
          <Volume2 className={`w-3.5 h-3.5 ${isPlaying ? "text-emerald-600 animate-bounce" : "text-slate-450"}`} />
          <span className="text-[9px] font-black uppercase tracking-wider text-slate-500 hidden sm:inline">
            Voice Reader
          </span>
          <span className={`w-1.5 h-1.5 rounded-full ${isPlaying ? (isPaused ? 'bg-amber-400' : 'bg-emerald-500 animate-ping') : 'bg-slate-350'}`} />
        </div>

        {/* Center: Essential Controls */}
        <div className="flex items-center gap-1.5">
          {!isPlaying ? (
            <button
              type="button"
              onClick={handlePlay}
              className="py-1 px-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-extrabold border-none flex items-center gap-1 cursor-pointer transition active:scale-95"
              title="Play narration"
            >
              <Play className="w-2.5 h-2.5 fill-current" /> Play
            </button>
          ) : isPaused ? (
            <button
              type="button"
              onClick={handleResume}
              className="py-1 px-2.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-[10px] font-extrabold border-none flex items-center gap-1 cursor-pointer transition active:scale-95"
              title="Resume narration"
            >
              <Play className="w-2.5 h-2.5 fill-current" /> Resume
            </button>
          ) : (
            <button
              type="button"
              onClick={handlePause}
              className="py-1 px-2.5 bg-amber-550 hover:bg-amber-600 text-slate-900 rounded-lg text-[10px] font-extrabold border-none flex items-center gap-1 cursor-pointer transition active:scale-95"
              title="Pause narration"
            >
              <Pause className="w-2.5 h-2.5 fill-current" /> Pause
            </button>
          )}

          <button
            type="button"
            disabled={!isPlaying}
            onClick={handleStop}
            className="py-1 px-2.5 bg-slate-200 hover:bg-slate-300 disabled:opacity-40 text-slate-700 rounded-lg text-[10px] font-extrabold border-none flex items-center gap-1 cursor-pointer transition active:scale-95"
            title="Stop narration completely"
          >
            <Square className="w-2.5 h-2.5 fill-current" /> Stop
          </button>

          {/* Mini Separator */}
          <span className="h-4 w-px bg-slate-300/60 mx-0.5" />

          {/* Female/Male voice selector */}
          <div className="bg-slate-200/50 p-0.5 rounded-lg flex border border-slate-300/30">
            <button
              type="button"
              onClick={() => {
                setGenderFilter('female');
                if (isPlaying) setTimeout(handlePlay, 100);
              }}
              className={`py-0.5 px-1.5 rounded-md text-[9px] font-extrabold transition border-none cursor-pointer ${
                genderFilter === 'female'
                  ? 'bg-white text-pink-600 shadow-xs'
                  : 'text-slate-500 hover:bg-slate-105'
              }`}
            >
              👧 F
            </button>
            <button
              type="button"
              onClick={() => {
                setGenderFilter('male');
                if (isPlaying) setTimeout(handlePlay, 100);
              }}
              className={`py-0.5 px-1.5 rounded-md text-[9px] font-extrabold transition border-none cursor-pointer ${
                genderFilter === 'male'
                  ? 'bg-white text-blue-900 shadow-xs'
                  : 'text-slate-500 hover:bg-slate-105'
              }`}
            >
              👦 M
            </button>
          </div>
        </div>

        {/* Right Side: Options Tweak Toggle */}
        <button
          type="button"
          onClick={() => setShowSettings(!showSettings)}
          className={`py-1 px-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-[9px] font-black border-none flex items-center gap-1 cursor-pointer transition active:scale-95 ${showSettings ? "bg-indigo-150 text-indigo-800" : ""}`}
          title="Tweak Speech settings"
        >
          <span>Speed/Vol</span>
          <RefreshCw className={`w-2.5 h-2.5 transition-transform duration-300 ${showSettings ? "rotate-180 text-indigo-750" : ""}`} />
        </button>
      </div>

      {/* Speed & Volume Collapsible Drawers */}
      {showSettings && (
        <div className="grid grid-cols-2 gap-4 mt-2 pt-1.5 border-t border-dashed border-slate-350/50 text-slate-605 text-[9px]">
          <div className="space-y-1">
            <div className="flex items-center justify-between font-black text-slate-500 uppercase tracking-wide">
              <span>🔊 Volume</span>
              <span className="font-mono text-[9px]">{Math.round(volume * 100)}%</span>
            </div>
            <input
              type="range"
              min="0"
              max="1"
              step="0.1"
              value={volume}
              onChange={(e) => {
                const val = parseFloat(e.target.value);
                setVolume(val);
                if (utteranceRef.current) utteranceRef.current.volume = val;
              }}
              className="w-full h-1 bg-slate-200 accent-emerald-600 rounded-lg cursor-pointer"
            />
          </div>

          <div className="space-y-1">
            <div className="flex items-center justify-between font-black text-slate-500 uppercase tracking-wide">
              <span>⚡ Speed rate</span>
              <span className="font-mono text-[9px]">{speed.toFixed(1)}x</span>
            </div>
            <input
              type="range"
              min="0.5"
              max="2.0"
              step="0.1"
              value={speed}
              onChange={(e) => {
                const val = parseFloat(e.target.value);
                setSpeed(val);
                if (utteranceRef.current) utteranceRef.current.rate = val;
              }}
              className="w-full h-1 bg-slate-200 accent-indigo-650 rounded-lg cursor-pointer"
            />
          </div>
        </div>
      )}
    </div>
  );
}
