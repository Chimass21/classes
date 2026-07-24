<?php
namespace App\Helpers;

class MathRenderer
{
    public static function render(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $html = $text;

        // Strip $$...$$ and $...$ LaTeX delimiters
        $html = preg_replace('/\$\$([\s\S]*?)\$\$/', '$1', $html);
        $html = preg_replace('/\$([^$\n]*?)\$/', '$1', $html);

        // Convert \frac{a}{b} to CSS fractions
        $html = preg_replace_callback('/\\\\frac\{([^}]+)\}\{([^}]+)\}/', function ($m) {
            $num = $m[1];
            $den = $m[2];
            return '<span class="inline-flex flex-col text-center align-middle mx-1" style="vertical-align: -0.35rem;">'
                . '<span class="border-b border-slate-700 pb-0.5 px-1.5 leading-none font-bold text-slate-800 font-mono" style="font-size: 11px;">' . $num . '</span>'
                . '<span class="pt-0.5 px-1.5 leading-none font-bold text-slate-800 font-mono" style="font-size: 11px;">' . $den . '</span>'
                . '</span>';
        }, $html);

        // Convert \sqrt[n]{x}
        $html = preg_replace_callback('/\\\\sqrt\[(\d+)\]\{([^}]+)\}/', function ($m) {
            return '<span class="font-bold text-emerald-700" style="font-size: 1.1em;">&#8731;<span style="font-size:0.6em;vertical-align:super;">' . $m[1] . '</span></span><span style="text-decoration:overline;" class="mx-px">' . $m[2] . '</span>';
        }, $html);

        // Convert \sqrt{x}
        $html = preg_replace_callback('/\\\\sqrt\{([^}]+)\}/', function ($m) {
            return '<span class="font-bold text-emerald-700" style="font-size: 1.2em;">&radic;</span><span style="text-decoration:overline;" class="mx-px">' . $m[1] . '</span>';
        }, $html);

        // LaTeX commands to HTML entities
        $replacements = [
            '/\\\\cdot\b/' => '&middot; ',
            '/\\\\times\b/' => '&times;',
            '/\\\\div\b/' => '&divide;',
            '/\\\\pm\b/' => '&plusmn;',
            '/\\\\rightarrow\b/' => '&rarr;',
            '/\\\\to\b/' => '&rarr;',
            '/\\\\Rightarrow\b/' => '&rArr;',
            '/\\\\leftarrow\b/' => '&larr;',
            '/\\\\gets\b/' => '&larr;',
            '/\\\\Leftarrow\b/' => '&lArr;',
            '/\\\\rightleftharpoons\b/' => '&#8652;',
            '/\\\\leftrightarrow\b/' => '&#8596;',
            '/\\\\rightleftarrows\b/' => '&#8646;',
            '/\\\\infty\b/' => '&infin;',
            '/\\\\partial\b/' => '&part;',
            '/\\\\prime\b/' => '&prime;',
            '/\\\\circ\b/' => '&deg;',
            '/\\\\angle\b/' => '&ang;',
            '/\\\\measuredangle\b/' => '&ang;',
            '/\\\\perp\b/' => '&perp;',
            '/\\\\parallel\b/' => '&parallel;',
            '/\\\\cup\b/' => '&cup;',
            '/\\\\cap\b/' => '&cap;',
            '/\\\\emptyset\b/' => '&empty;',
            '/\\\\varnothing\b/' => '&empty;',
            '/\\\\geq\b/' => '&ge;',
            '/\\\\leq\b/' => '&le;',
            '/\\\\neq\b/' => '&ne;',
            '/\\\\approx\b/' => '&asymp;',
            '/\\\\equiv\b/' => '&equiv;',
            '/\\\\propto\b/' => '&prop;',
            '/\\\\subset\b/' => '&sub;',
            '/\\\\supset\b/' => '&sup;',
            '/\\\\subseteq\b/' => '&sube;',
            '/\\\\supseteq\b/' => '&supe;',
            '/\\\\int\b/' => '&int;',
            '/\\\\iint\b/' => '&iint;',
            '/\\\\iiint\b/' => '&iiint;',
            '/\\\\oint\b/' => '&oint;',
            '/\\\\sum\b/' => '&sum;',
            '/\\\\prod\b/' => '&prod;',
        ];
        $html = preg_replace(array_keys($replacements), array_values($replacements), $html);

        // Greek letters
        $greek = [
            '/\\\\alpha\b/' => '&alpha;',
            '/\\\\beta\b/' => '&beta;',
            '/\\\\gamma\b/' => '&gamma;',
            '/\\\\delta\b/' => '&delta;',
            '/\\\\epsilon\b/' => '&epsilon;',
            '/\\\\varepsilon\b/' => '&epsilon;',
            '/\\\\zeta\b/' => '&zeta;',
            '/\\\\eta\b/' => '&eta;',
            '/\\\\theta\b/' => '&theta;',
            '/\\\\iota\b/' => '&iota;',
            '/\\\\kappa\b/' => '&kappa;',
            '/\\\\lambda\b/' => '&lambda;',
            '/\\\\mu\b/' => '&mu;',
            '/\\\\nu\b/' => '&nu;',
            '/\\\\xi\b/' => '&xi;',
            '/\\\\omicron\b/' => '&omicron;',
            '/\\\\pi\b/' => '&pi;',
            '/\\\\rho\b/' => '&rho;',
            '/\\\\sigma\b/' => '&sigma;',
            '/\\\\tau\b/' => '&tau;',
            '/\\\\upsilon\b/' => '&upsilon;',
            '/\\\\phi\b/' => '&phi;',
            '/\\\\varphi\b/' => '&phi;',
            '/\\\\chi\b/' => '&chi;',
            '/\\\\psi\b/' => '&psi;',
            '/\\\\omega\b/' => '&omega;',
            '/\\\\Alpha\b/' => '&Alpha;',
            '/\\\\Beta\b/' => '&Beta;',
            '/\\\\Gamma\b/' => '&Gamma;',
            '/\\\\Delta\b/' => '&Delta;',
            '/\\\\Theta\b/' => '&Theta;',
            '/\\\\Lambda\b/' => '&Lambda;',
            '/\\\\Xi\b/' => '&Xi;',
            '/\\\\Pi\b/' => '&Pi;',
            '/\\\\Sigma\b/' => '&Sigma;',
            '/\\\\Phi\b/' => '&Phi;',
            '/\\\\Psi\b/' => '&Psi;',
            '/\\\\Omega\b/' => '&Omega;',
        ];
        $html = preg_replace(array_keys($greek), array_values($greek), $html);

        // \sin, \cos, \tan, \log, \ln, \sec, \csc, \cot
        $funcs = ['sin', 'cos', 'tan', 'log', 'ln', 'sec', 'csc', 'cot'];
        foreach ($funcs as $fn) {
            $html = preg_replace('/\\\\' . $fn . '\b/', '<span class="font-serif italic mx-px">' . $fn . '</span>', $html);
        }

        // ^{expression} superscripts
        $html = preg_replace('/\^+\{([^}]+)\}/', '<sup class="font-bold text-pink-700 mx-px" style="font-size: 9px;">$1</sup>', $html);
        // Simple ^x or ^2
        $html = preg_replace('/\^+([a-zA-Z0-9+\-=()]+)/', '<sup class="font-bold text-pink-700 mx-px" style="font-size: 9px;">$1</sup>', $html);

        // _{expression} subscripts
        $html = preg_replace('/_+\{([^}]+)\}/', '<sub class="font-bold text-indigo-800 mx-px" style="font-size: 9px;">$1</sub>', $html);
        // Simple _1 or _n
        $html = preg_replace('/_+([a-zA-Z0-9+\-=()]+)/', '<sub class="font-bold text-indigo-800 mx-px" style="font-size: 9px;">$1</sub>', $html);

        return $html;
    }
}