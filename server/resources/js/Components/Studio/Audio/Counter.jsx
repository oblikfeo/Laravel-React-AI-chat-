/** Счётчик символов под полем ввода. */
export default function Counter({ value, max }) {
    return (
        <div className="mt-1 text-right text-[12px] text-white/35">
            {value}/{max}
        </div>
    );
}
