export function renderFormattedMath(text: string | null | undefined): string {
  if (!text) return "";

  let html = text;

  // 1. Strip LaTeX math mode delimiters: $$...$$ and $...$
  html = html.replace(/\$\$([\s\S]*?)\$\$/g, "$1");
  html = html.replace(/\$([^$\n]*?)\$/g, "$1");

  // 2. Escape basic HTML characters
  html = html
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");

  // 2. Convert LaTeX \text{} to plain text
  html = html.replace(/\\text\{([^}]+)\}/g, "$1");

  // 3. Handle LaTeX \cdot (multiplication dot)
  html = html.replace(/\\cdot\s*/g, "&middot; ");

  // 4. Handle LaTeX \times
  html = html.replace(/\\times\b/g, "&times;");

  // 5. Handle LaTeX \div
  html = html.replace(/\\div\b/g, "&divide;");

  // 6. Handle LaTeX \pm
  html = html.replace(/\\pm\b/g, "&plusmn;");

  // 7. Handle \rightarrow, \to, \Rightarrow
  html = html.replace(/\\rightarrow\b/g, "&rarr;");
  html = html.replace(/\\to\b/g, "&rarr;");
  html = html.replace(/\\Rightarrow\b/g, "&rArr;");

  // 8. Handle \leftarrow, \gets, \Leftarrow
  html = html.replace(/\\leftarrow\b/g, "&larr;");
  html = html.replace(/\\gets\b/g, "&larr;");
  html = html.replace(/\\Leftarrow\b/g, "&lArr;");

  // 9. Handle \rightleftharpoons, \leftrightarrow, \rightleftarrows
  html = html.replace(/\\rightleftharpoons\b/g, "&#8652;");
  html = html.replace(/\\leftrightarrow\b/g, "&#8596;");
  html = html.replace(/\\rightleftarrows\b/g, "&#8646;");

  // 10. Handle \sin, \cos, \tan, \log, \ln, \sec, \csc, \cot
  html = html.replace(/\\sin\b/g, "<span class='font-serif italic mx-px'>sin</span>");
  html = html.replace(/\\cos\b/g, "<span class='font-serif italic mx-px'>cos</span>");
  html = html.replace(/\\tan\b/g, "<span class='font-serif italic mx-px'>tan</span>");
  html = html.replace(/\\log\b/g, "<span class='font-serif italic mx-px'>log</span>");
  html = html.replace(/\\ln\b/g, "<span class='font-serif italic mx-px'>ln</span>");
  html = html.replace(/\\sec\b/g, "<span class='font-serif italic mx-px'>sec</span>");
  html = html.replace(/\\csc\b/g, "<span class='font-serif italic mx-px'>csc</span>");
  html = html.replace(/\\cot\b/g, "<span class='font-serif italic mx-px'>cot</span>");

  // 11. Handle \sqrt[n]{x} and \sqrt{x}
  html = html.replace(/\\sqrt\[(\d+)\]\{([^}]+)\}/g, (match, n, radicand) => {
    return `<span class='font-bold text-emerald-700' style='font-size: 1.1em;'>&#8731;<span style='font-size:0.6em;vertical-align:super;'>${n}</span></span><span style='text-decoration:overline;' class='mx-px'>${radicand}</span>`;
  });
  html = html.replace(/\\sqrt\{([^}]+)\}/g, (match, radicand) => {
    return `<span class='font-bold text-emerald-700' style='font-size: 1.2em;'>&radic;</span><span style='text-decoration:overline;' class='mx-px'>${radicand}</span>`;
  });

  // 12. Handle \cbrt (cube root)
  html = html.replace(/\\cbrt\{([^}]+)\}/g, (match, radicand) => {
    return `<span class='font-bold text-emerald-700' style='font-size: 1.1em;'>&#8731;</span><span style='text-decoration:overline;' class='mx-px'>${radicand}</span>`;
  });

  // 13. Handle LaTeX fractions: \frac{numerator}{denominator}
  const fracRegex = /\\+frac\{([^}]+)\}\{([^}]+)\}/g;
  while (fracRegex.test(html)) {
    html = html.replace(fracRegex, (match, num, den) => {
      return `<span class="inline-flex flex-col text-center align-middle mx-1" style="vertical-align: -0.35rem;"><span class="border-b border-slate-700 pb-0.5 px-1.5 leading-none font-bold text-slate-800 font-mono" style="font-size: 11px;">${num}</span><span class="pt-0.5 px-1.5 leading-none font-bold text-slate-800 font-mono" style="font-size: 11px;">${den}</span></span>`;
    });
  }

  // 14. Handle LaTeX superscripts: ^{expression}
  html = html.replace(/\^+\{([^}]+)\}/g, "<sup class='font-bold text-pink-700 mx-px' style='font-size: 9px;'>$1</sup>");
  // Simple ^x or ^2
  html = html.replace(/\^+([a-zA-Z0-9+\-=()]+)/g, "<sup class='font-bold text-pink-700 mx-px' style='font-size: 9px;'>$1</sup>");

  // 15. Handle LaTeX subscripts: _{expression}
  html = html.replace(/_+\{([^}]+)\}/g, "<sub class='font-bold text-indigo-800 mx-px' style='font-size: 9px;'>$1</sub>");
  // Simple _1 or _n
  html = html.replace(/_+([a-zA-Z0-9+\-=()]+)/g, "<sub class='font-bold text-indigo-800 mx-px' style='font-size: 9px;'>$1</sub>");

  // 16. Handle \alpha, \beta, \gamma, etc.
  const greekMap: Record<string, string> = {
    '\\alpha': '&alpha;', '\\beta': '&beta;', '\\gamma': '&gamma;',
    '\\delta': '&delta;', '\\epsilon': '&epsilon;', '\\varepsilon': '&epsilon;',
    '\\zeta': '&zeta;', '\\eta': '&eta;', '\\theta': '&theta;',
    '\\iota': '&iota;', '\\kappa': '&kappa;', '\\lambda': '&lambda;',
    '\\mu': '&mu;', '\\nu': '&nu;', '\\xi': '&xi;', '\\omicron': '&omicron;',
    '\\pi': '&pi;', '\\rho': '&rho;', '\\sigma': '&sigma;',
    '\\tau': '&tau;', '\\upsilon': '&upsilon;', '\\phi': '&phi;',
    '\\varphi': '&phi;', '\\chi': '&chi;', '\\psi': '&psi;',
    '\\omega': '&omega;',
    '\\Alpha': '&Alpha;', '\\Beta': '&Beta;', '\\Gamma': '&Gamma;',
    '\\Delta': '&Delta;', '\\Theta': '&Theta;', '\\Lambda': '&Lambda;',
    '\\Xi': '&Xi;', '\\Pi': '&Pi;', '\\Sigma': '&Sigma;',
    '\\Phi': '&Phi;', '\\Psi': '&Psi;', '\\Omega': '&Omega;',
  };
  for (const [latex, entity] of Object.entries(greekMap)) {
    html = html.replace(new RegExp(latex + '\\b', 'g'), `<span class='font-bold text-slate-800 mx-px'>${entity}</span>`);
  }

  // 17. Handle \infty
  html = html.replace(/\\infty\b/g, '<span class="font-bold text-slate-800">&infin;</span>');

  // 18. Handle \partial
  html = html.replace(/\\partial\b/g, '<span class="font-bold text-slate-800">&part;</span>');

  // 19. Handle \geq, \leq, \neq, \approx, \equiv, \propto, \subset, \supset
  html = html.replace(/\\geq\b/g, '&ge;');
  html = html.replace(/\\leq\b/g, '&le;');
  html = html.replace(/\\neq\b/g, '&ne;');
  html = html.replace(/\\approx\b/g, '&asymp;');
  html = html.replace(/\\equiv\b/g, '&equiv;');
  html = html.replace(/\\propto\b/g, '&prop;');
  html = html.replace(/\\subset\b/g, '&sub;');
  html = html.replace(/\\supset\b/g, '&sup;');
  html = html.replace(/\\subseteq\b/g, '&sube;');
  html = html.replace(/\\supseteq\b/g, '&supe;');

  // 20. Handle \cup, \cap, \emptyset, \varnothing
  html = html.replace(/\\cup\b/g, '&cup;');
  html = html.replace(/\\cap\b/g, '&cap;');
  html = html.replace(/\\emptyset\b/g, '&empty;');
  html = html.replace(/\\varnothing\b/g, '&empty;');

  // 21. Handle \int, \iint, \iiint, \oint
  html = html.replace(/\\iint\b/g, '&iint;');
  html = html.replace(/\\iiint\b/g, '&iiint;');
  html = html.replace(/\\oint\b/g, '&oint;');
  html = html.replace(/\\int\b/g, '&int;');

  // 22. Handle \sum, \prod
  html = html.replace(/\\sum\b/g, '&sum;');
  html = html.replace(/\\prod\b/g, '&prod;');

  // 23. Handle \angle, \measuredangle
  html = html.replace(/\\angle\b/g, '&ang;');
  html = html.replace(/\\measuredangle\b/g, '&ang;');

  // 24. Handle \perp, \parallel
  html = html.replace(/\\perp\b/g, '&perp;');
  html = html.replace(/\\parallel\b/g, '&parallel;');

  // 25. Handle \prime, \dagger
  html = html.replace(/\\prime\b/g, '&prime;');
  html = html.replace(/\\dagger\b/g, '&dagger;');

  // 26. Handle \circ (degrees)
  html = html.replace(/\\circ\b/g, '&deg;');

  // 27. Auto-format standard inline fractional numbers e.g. 1/2, 3/4
  html = html.replace(/\b(\d{1,3})\/(\d{1,3})\b(?!\s*[a-zA-Z])/g, (match, num, den) => {
    return `<span class="inline-flex flex-col text-center align-middle mx-1" style="vertical-align: -0.35rem;"><span class="border-b border-slate-700 pb-0.5 px-1.5 leading-none font-bold text-slate-800 font-mono" style="font-size: 11px;">${num}</span><span class="pt-0.5 px-1.5 leading-none font-bold text-slate-800 font-mono" style="font-size: 11px;">${den}</span></span>`;
  });

  // 28. Auto-format slash division in algebraic expressions: (a+b)/(c+d) or similar
  html = html.replace(/\(([^)]+)\)\/(\([^)]+\))/g, (match, num, den) => {
    return `<span class="inline-flex flex-col text-center align-middle mx-1" style="vertical-align: -0.35rem;"><span class="border-b border-slate-700 pb-0.5 px-1.5 leading-none font-bold text-slate-800 font-mono" style="font-size: 11px;">(${num})</span><span class="pt-0.5 px-1.5 leading-none font-bold text-slate-800 font-mono" style="font-size: 11px;">(${den})</span></span>`;
  });

  // 29. Auto-format chemical formulas (element followed by number)
  const chemElements = "H|He|Li|Be|B|C|N|O|F|Ne|Na|Mg|Al|Si|P|S|Cl|Ar|K|Ca|Ti|V|Cr|Mn|Fe|Co|Ni|Cu|Zn|Br|Ag|I|Pt|Au|Hg|Pb|U|Sr|Ba|Ra|Cd|Sn|Sb|Xe|Kr|Rn|Ce|Pr|Nd|Pm|Sm|Eu|Gd|Tb|Dy|Ho|Er|Tm|Yb|Lu|Th|Pa|Np|Pu|Am|Cm|Bk|Cf|Es|Fm|Md|No|Lr|Ac|Sc|Y|Zr|Nb|Mo|Tc|Ru|Rh|Pd|Os|Ir|Rb|Cs|Fr|Be|Mg|Ca|Sr|Ba|Ra|B|Al|Ga|In|Tl|C|Si|Ge|Sn|Pb|N|P|As|Sb|Bi|O|S|Se|Te|Po|F|Cl|Br|I|At|He|Ne|Ar|Kr|Xe|Rn";
  const symbolsArray = chemElements.split("|");
  for (const el of symbolsArray) {
    const elDigRegex = new RegExp(`\\b(${el})(\\d+)(?=[A-Z\\(]|\\)|\\+|-|\\s|\\b|$)`, "g");
    html = html.replace(elDigRegex, "$1<sub class='font-black text-indigo-800 mx-px' style='font-size: 9px;'>$2</sub>");
  }

  // 30. Also catch brackets followed by coefficients: (OH)2, (SO4)3
  html = html.replace(/(\))(\d+)(?=[A-Z\(\)]|\+|-|\s|\\b|$)/g, "$1<sub class='font-black text-indigo-800 mx-px' style='font-size: 9px;'>$2</sub>");

  // 31. Standard chemical reaction arrows: -->, ->, =>, &rarr;
  html = html.replace(/\s*(--&gt;|-&gt;|=&gt;|&rarr;)\s*/g, " <span class='text-emerald-700 font-extrabold mx-2 text-sm'>&rarr;</span> ");
  html = html.replace(/\s*(&harr;|&lt;-&gt;)\s*/g, " <span class='text-emerald-700 font-extrabold mx-2 text-sm'>&harr;</span> ");

  // 32. Auto-format common ions/charges
  const ionPatterns: [RegExp, string][] = [
    [/\bNa\+/g, 'Na<sup class="font-bold text-pink-700 mx-px" style="font-size:9px;">+</sup>'],
    [/\bK\+/g, 'K<sup class="font-bold text-pink-700 mx-px" style="font-size:9px;">+</sup>'],
    [/\bCl-/g, 'Cl<sup class="font-bold text-pink-700 mx-px" style="font-size:9px;">-</sup>'],
    [/\bMg2\+/g, 'Mg<sup class="font-bold text-pink-700 mx-px" style="font-size:9px;">2+</sup>'],
    [/\bCa2\+/g, 'Ca<sup class="font-bold text-pink-700 mx-px" style="font-size:9px;">2+</sup>'],
    [/\bOH-/g, 'OH<sup class="font-bold text-pink-700 mx-px" style="font-size:9px;">-</sup>'],
    [/\bCO32-/g, 'CO<sub class="font-black text-indigo-800 mx-px" style="font-size:9px;">3</sub><sup class="font-bold text-pink-700 mx-px" style="font-size:9px;">2-</sup>'],
    [/\bNH4\+/g, 'NH<sub class="font-black text-indigo-800 mx-px" style="font-size:9px;">4</sub><sup class="font-bold text-pink-700 mx-px" style="font-size:9px;">+</sup>'],
    [/\bH\+/g, 'H<sup class="font-bold text-pink-700 mx-px" style="font-size:9px;">+</sup>'],
    [/\bFe2\+/g, 'Fe<sup class="font-bold text-pink-700 mx-px" style="font-size:9px;">2+</sup>'],
    [/\bFe3\+/g, 'Fe<sup class="font-bold text-pink-700 mx-px" style="font-size:9px;">3+</sup>'],
    [/\bAl3\+/g, 'Al<sup class="font-bold text-pink-700 mx-px" style="font-size:9px;">3+</sup>'],
    [/\bSO42-/g, 'SO<sub class="font-black text-indigo-800 mx-px" style="font-size:9px;">4</sub><sup class="font-bold text-pink-700 mx-px" style="font-size:9px;">2-</sup>'],
  ];
  for (const [pattern, replacement] of ionPatterns) {
    html = html.replace(pattern, replacement);
  }

  // 33. Auto-format general charge patterns like X^{2+}, X^{3-}
  html = html.replace(/\^+\{(\d*[+-])\}/g, "<sup class='font-bold text-pink-700 mx-px' style='font-size: 9px;'>$1</sup>");

  // 34. Convert newlines to HTML breaks
  html = html.replace(/\n/g, "<br />");

  return html;
}