/// <reference types="vite/client" />

// Diz ao TypeScript quais variáveis de ambiente existem (arquivo .env).
interface ImportMetaEnv {
  readonly VITE_API_URL?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
