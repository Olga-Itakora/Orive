import * as React from "react";
import { cn } from "@/lib/utils";

const fieldBase =
  "w-full rounded-[var(--radius-md)] border border-line-strong bg-surface px-3 text-base text-ink placeholder:text-subtle focus-visible:outline-2 focus-visible:outline-petrol-600 aria-[invalid=true]:border-danger disabled:bg-paper disabled:text-muted sm:text-sm";

export const Input = React.forwardRef<HTMLInputElement, React.InputHTMLAttributes<HTMLInputElement>>(({ className, ...props }, ref) => (
  <input ref={ref} className={cn(fieldBase, "h-11", className)} {...props} />
));
Input.displayName = "Input";

export const Textarea = React.forwardRef<HTMLTextAreaElement, React.TextareaHTMLAttributes<HTMLTextAreaElement>>(({ className, rows = 4, ...props }, ref) => (
  <textarea ref={ref} rows={rows} className={cn(fieldBase, "py-2 leading-relaxed", className)} {...props} />
));
Textarea.displayName = "Textarea";

export const Select = React.forwardRef<HTMLSelectElement, React.SelectHTMLAttributes<HTMLSelectElement>>(({ className, children, ...props }, ref) => (
  <select ref={ref} className={cn(fieldBase, "h-11 pr-8", className)} {...props}>
    {children}
  </select>
));
Select.displayName = "Select";

export function Label({ className, ...props }: React.LabelHTMLAttributes<HTMLLabelElement>) {
  return <label className={cn("text-sm font-medium text-ink", className)} {...props} />;
}

/** Campo con etiqueta visible, ayuda y error accesibles. */
export function Field({
  id,
  label,
  hint,
  error,
  optional,
  required,
  children,
  className,
}: {
  id: string;
  label: React.ReactNode;
  hint?: React.ReactNode;
  error?: string | null;
  optional?: boolean;
  /** Marca visible de campo obligatorio y atributo required para la validación al salir del campo. */
  required?: boolean;
  children: React.ReactElement<{ id?: string; "aria-describedby"?: string; "aria-invalid"?: boolean }>;
  className?: string;
}) {
  const hintId = hint ? `${id}-hint` : undefined;
  const errId = `${id}-error`;
  const describedBy = [hintId, errId].filter(Boolean).join(" ");
  return (
    <div className={cn("space-y-2", className)}>
      <Label htmlFor={id}>
        {label}
        {optional && <span className="ml-2 text-xs font-normal text-muted">(opcional)</span>}
        {required && (
          <span className="ml-1 text-danger" aria-hidden>
            *
          </span>
        )}
        {required && <span className="sr-only"> (obligatorio)</span>}
      </Label>
      {React.cloneElement(children as React.ReactElement<Record<string, unknown>>, {
        id,
        "aria-describedby": describedBy,
        "aria-invalid": error ? true : undefined,
        ...(required ? { required: true } : {}),
      })}
      {hint && (
        <p id={hintId} className="text-sm text-muted">
          {hint}
        </p>
      )}
      <p id={errId} data-field-error className="text-sm font-medium text-danger" role="alert" hidden={!error}>
        {error}
      </p>
    </div>
  );
}

/** Grupo de opciones (radio o checkbox) con leyenda accesible. */
export function ChoiceGroup({
  legend,
  hint,
  error,
  children,
  className,
}: {
  legend: React.ReactNode;
  hint?: React.ReactNode;
  error?: string | null;
  children: React.ReactNode;
  className?: string;
}) {
  return (
    <fieldset className={cn("space-y-2", className)}>
      <legend className="mb-1 text-sm font-medium text-ink">{legend}</legend>
      {hint && <p className="-mt-1 mb-2 text-sm text-muted">{hint}</p>}
      <div className="space-y-2">{children}</div>
      <p data-field-error className="text-sm font-medium text-danger" role="alert" hidden={!error}>
        {error}
      </p>
    </fieldset>
  );
}

export function Choice({
  type = "radio",
  name,
  value,
  label,
  description,
  defaultChecked,
  checked,
  onChange,
  disabled,
}: {
  type?: "radio" | "checkbox";
  name: string;
  value: string;
  label: React.ReactNode;
  description?: React.ReactNode;
  defaultChecked?: boolean;
  checked?: boolean;
  onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
  disabled?: boolean;
}) {
  const id = `${name}-${value}`.replace(/[^a-zA-Z0-9_-]/g, "_");
  return (
    <label
      htmlFor={id}
      className="flex min-h-11 cursor-pointer items-start gap-3 rounded-[var(--radius-md)] border border-line bg-surface p-3 hover:border-line-strong has-[:checked]:border-petrol-600 has-[:checked]:bg-petrol-50 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-petrol-600"
    >
      <input
        id={id}
        type={type}
        name={name}
        value={value}
        defaultChecked={defaultChecked}
        checked={checked}
        onChange={onChange}
        disabled={disabled}
        className="mt-1 h-4 w-4 shrink-0 accent-[var(--color-petrol-800)]"
      />
      <span className="text-sm">
        <span className="font-medium text-ink">{label}</span>
        {description && <span className="mt-1 block text-sm text-muted">{description}</span>}
      </span>
    </label>
  );
}
