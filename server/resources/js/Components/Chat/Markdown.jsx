import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';

/**
 * Разметка в ответе модели.
 *
 * Модель отвечает в Markdown: **жирный**, списки, заголовки, таблицы,
 * код. Без обработки всё это показывалось звёздочками и решётками.
 *
 * HTML из текста модели намеренно не разрешаем: react-markdown по
 * умолчанию его не вставляет, поэтому разметка из ответа не может
 * выполнить чужой код на странице.
 */
export default function Markdown({ children }) {
    return (
        <div className="space-y-3 text-[15px] leading-relaxed text-white/95">
            <ReactMarkdown remarkPlugins={[remarkGfm]} components={components}>
                {children}
            </ReactMarkdown>
        </div>
    );
}

const components = {
    p: ({ children }) => <p className="whitespace-pre-wrap">{children}</p>,

    strong: ({ children }) => (
        <strong className="font-semibold text-white">{children}</strong>
    ),

    em: ({ children }) => <em className="italic">{children}</em>,

    // Заголовки внутри ответа не должны спорить с заголовками страницы,
    // поэтому разница между уровнями небольшая.
    h1: ({ children }) => (
        <p className="pt-1 text-[17px] font-semibold text-white">{children}</p>
    ),
    h2: ({ children }) => (
        <p className="pt-1 text-[16px] font-semibold text-white">{children}</p>
    ),
    h3: ({ children }) => (
        <p className="pt-1 text-[15px] font-semibold text-white">{children}</p>
    ),

    ul: ({ children }) => (
        <ul className="list-disc space-y-1.5 pl-5 marker:text-white/35">
            {children}
        </ul>
    ),
    ol: ({ children }) => (
        <ol className="list-decimal space-y-1.5 pl-5 marker:text-white/35">
            {children}
        </ol>
    ),
    li: ({ children }) => <li className="pl-0.5">{children}</li>,

    blockquote: ({ children }) => (
        <blockquote className="border-l-2 border-white/20 pl-4 text-white/70">
            {children}
        </blockquote>
    ),

    code: ({ inline, children }) =>
        inline ? (
            <code className="rounded bg-white/10 px-1.5 py-0.5 font-mono text-[13px] text-sky-200">
                {children}
            </code>
        ) : (
            <code className="font-mono text-[13px]">{children}</code>
        ),

    pre: ({ children }) => (
        <pre className="overflow-x-auto rounded-xl border border-white/10 bg-black/40 p-4 scrollbar-thin">
            {children}
        </pre>
    ),

    a: ({ href, children }) => (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            className="text-sky-300 underline underline-offset-2 hover:text-sky-200"
        >
            {children}
        </a>
    ),

    hr: () => <hr className="border-white/10" />,

    table: ({ children }) => (
        <div className="overflow-x-auto scrollbar-thin">
            <table className="w-full border-collapse text-sm">{children}</table>
        </div>
    ),
    th: ({ children }) => (
        <th className="border border-white/10 px-3 py-2 text-left font-semibold text-white">
            {children}
        </th>
    ),
    td: ({ children }) => (
        <td className="border border-white/10 px-3 py-2 align-top">
            {children}
        </td>
    ),
};
