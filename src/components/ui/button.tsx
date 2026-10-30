import * as React from "react";
import Link from "next/link";
import { cva, type VariantProps } from "class-variance-authority";
import { cn } from "@/lib/utils";

export const buttonVariants = cva(
  "relative inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-[var(--radius-md)] text-sm font-medium disabled:pointer-events-none disabled:opacity-50 select-none",
  {
    variants: {
      variant: {
        primary: "bg-petrol-800 text-white hover:bg-petrol-700",
        secondary: "border border-line-strong bg-surface text-ink hover:bg-petrol-50",
        ghost: "text-petrol-800 hover:bg-petrol-50",
        danger: "bg-danger text-white hover:opacity-90",
        link: "text-petrol-700 underline underline-offset-4 hover:text-petrol-900 !px-0 min-h-11",
      },
      size: {
        // sm: menos relleno horizontal, misma altura táctil mínima (44px).
        sm: "h-11 px-3",
        md: "h-11 px-4",
        lg: "h-12 px-6 text-base",
        icon: "h-11 w-11",
      },
    },
    defaultVariants: { variant: "primary", size: "md" },
  },
);

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement>, VariantProps<typeof buttonVariants> {}

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(({ className, variant, size, ...props }, ref) => (
  <button ref={ref} className={cn(buttonVariants({ variant, size }), className)} {...props} />
));
Button.displayName = "Button";

export function ButtonLink({ href, className, variant, size, children, ...rest }: { href: string; className?: string; children: React.ReactNode } & VariantProps<typeof buttonVariants> & Omit<React.AnchorHTMLAttributes<HTMLAnchorElement>, "href">) {
  return (
    <Link href={href} className={cn(buttonVariants({ variant, size }), className)} {...rest}>
      {children}
    </Link>
  );
}

/** Ancla nativa con estilo de botón: para descargas y rutas que no son páginas. */
export function ButtonAnchor({ href, className, variant, size, children, ...rest }: { href: string; className?: string; children: React.ReactNode } & VariantProps<typeof buttonVariants> & Omit<React.AnchorHTMLAttributes<HTMLAnchorElement>, "href">) {
  return (
    <a href={href} className={cn(buttonVariants({ variant, size }), className)} {...rest}>
      {children}
    </a>
  );
}
