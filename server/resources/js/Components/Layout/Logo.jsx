import { useId } from 'react';

/**
 * Логотип Uncensia: круглый значок с градиентом и искрой + опциональная надпись.
 *
 * Идентификатор градиента уникален для каждого экземпляра: когда логотип
 * присутствует на странице дважды, общий id приводит к тому, что второй
 * значок остаётся без заливки.
 */
export function LogoMark({ className = 'h-8 w-8' }) {
    const gradientId = useId();

    return (
        <svg viewBox="0 0 32 32" fill="none" className={className}>
            <defs>
                <linearGradient id={gradientId} x1="4" y1="28" x2="28" y2="4">
                    <stop offset="0%" stopColor="#7c3aed" />
                    <stop offset="45%" stopColor="#a78bfa" />
                    <stop offset="100%" stopColor="#7dd3fc" />
                </linearGradient>
            </defs>

            <path
                d="M9 6v14.5a6.5 6.5 0 1 0 6.5-6.5H9"
                stroke={`url(#${gradientId})`}
                strokeWidth="3.4"
                strokeLinecap="round"
                strokeLinejoin="round"
                fill="none"
            />
            <circle cx="15.5" cy="20.5" r="3.2" fill={`url(#${gradientId})`} />
            <path
                d="M24 5.5l.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9z"
                fill="#ffffff"
            />
        </svg>
    );
}

export function LogoFull({ className = '' }) {
    return (
        <span className={`flex items-center gap-2.5 ${className}`}>
            <LogoMark className="h-7 w-7" />
            <span className="text-xl font-light tracking-tight text-white">
                uncensia
            </span>
        </span>
    );
}
