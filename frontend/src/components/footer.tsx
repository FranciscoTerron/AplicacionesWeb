export function Footer() {
  return (
    <footer className="mt-16 border-t bg-muted/30">
      <div className="mx-auto max-w-7xl px-4 py-8 text-sm text-muted-foreground">
        <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
          <p className="font-semibold text-foreground">MA Piscinas</p>
          <p>Todo para tu piscina — venta online de piscinas y accesorios.</p>
          <p>© {new Date().getFullYear()} MA Piscinas. Proyecto universitario.</p>
        </div>
      </div>
    </footer>
  );
}
