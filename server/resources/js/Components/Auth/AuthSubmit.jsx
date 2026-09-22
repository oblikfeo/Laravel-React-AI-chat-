/**
 * Кнопка отправки формы авторизации.
 *
 * Содержимое центрируется через flex, а не через text-align: при
 * блочной вёрстке пробельные узлы вокруг JSX-выражения сдвигают
 * текст вбок.
 */
export default function AuthSubmit({ children, disabled = false }) {
    return (
        <button
            type="submit"
            disabled={disabled}
            className="flex h-12 w-full items-center justify-center rounded-full bg-gradient-to-r from-[#dff3ff] to-[#eaf6ff] text-[15px] font-semibold text-black transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-50"
        >
            {children}
        </button>
    );
}
