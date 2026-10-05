import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react'
import { ApiError } from '../api/client'
import { createProduct, updateProduct } from '../api/catalog'
import type { Product, ProductInput } from '../types'
import { Icon } from './Icon'
import { MoneyInput } from './MoneyInput'

/*
 * Janela de cadastro/edição de produto.
 *  - Sem "product": cadastra um produto novo.
 *  - Com "product": edita o produto existente.
 *
 * A validação que vale é a do backend (ProductRequest). Aqui a tela só
 * mostra os erros que ele devolver, cada um embaixo do seu campo.
 */

interface Props {
  product: Product | null
  onClose: () => void
  onSaved: (product: Product) => void
}

export function ProductFormDialog({ product, onClose, onSaved }: Props) {
  const queryClient = useQueryClient()
  const dialogRef = useRef<HTMLDialogElement>(null)
  const isEditing = product !== null

  // Estado do formulário. Ao editar, começa com os dados atuais do produto.
  const [form, setForm] = useState<ProductInput>({
    code: product?.code ?? '',
    name: product?.name ?? '',
    price_cents: product?.price_cents ?? 0,
    stock: product?.stock ?? 0,
    active: product?.active ?? true,
  })
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [generalError, setGeneralError] = useState<string | null>(null)

  useEffect(() => {
    dialogRef.current?.showModal()
  }, [])

  const saveMutation = useMutation({
    mutationFn: (input: ProductInput) => (isEditing ? updateProduct(product.id, input) : createProduct(input)),
    onSuccess: (saved) => {
      // O cadastro e a busca do caixa precisam buscar os dados de novo.
      void queryClient.invalidateQueries({ queryKey: ['catalog'] })
      void queryClient.invalidateQueries({ queryKey: ['products'] })
      onSaved(saved)
    },
    onError: (error) => {
      if (error instanceof ApiError && error.status === 422) {
        // Pega a primeira mensagem de cada campo: { code: "Já existe..." }
        setErrors(Object.fromEntries(Object.entries(error.fieldErrors).map(([field, msgs]) => [field, msgs[0]])))
      } else {
        setGeneralError(error.message)
      }
    },
  })

  // Atualiza um campo e apaga o erro dele (o usuário está corrigindo).
  function setField<K extends keyof ProductInput>(field: K, value: ProductInput[K]) {
    setForm((current) => ({ ...current, [field]: value }))
    setErrors(({ [field]: _removed, ...rest }) => rest)
  }

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setErrors({})
    setGeneralError(null)
    saveMutation.mutate({ ...form, code: form.code.trim(), name: form.name.trim() })
  }

  const saving = saveMutation.isPending

  return (
    <dialog
      ref={dialogRef}
      className="dialog"
      aria-labelledby="product-form-title"
      onCancel={(event) => {
        event.preventDefault()
        if (!saving) onClose()
      }}
    >
      <form onSubmit={handleSubmit} noValidate>
        <header className="dialog__header">
          <h2 id="product-form-title">{isEditing ? 'Editar produto' : 'Novo produto'}</h2>
        </header>

        {generalError && (
          <p className="alert" role="alert">
            <Icon name="alert" size={20} /> {generalError}
          </p>
        )}

        <fieldset className="form-grid" disabled={saving}>
          <Field label="Código (código de barras)" error={errors.code}>
            <input
              value={form.code}
              onChange={(event) => setField('code', event.target.value)}
              placeholder="Ex: 7891000100103"
              autoFocus
            />
          </Field>

          <Field label="Nome" error={errors.name}>
            <input
              value={form.name}
              onChange={(event) => setField('name', event.target.value)}
              placeholder="Ex: Arroz Branco 5kg"
            />
          </Field>

          <Field label="Preço" error={errors.price_cents}>
            {/* Mesmo campo do valor recebido: 2-5-9-0 = R$ 25,90. */}
            <MoneyInput valueCents={form.price_cents} onChangeCents={(cents) => setField('price_cents', cents)} />
          </Field>

          <Field label="Estoque (unidades)" error={errors.stock}>
            <input
              inputMode="numeric"
              value={String(form.stock)}
              // Só dígitos: estoque é um número inteiro de unidades.
              onChange={(event) => setField('stock', Number(event.target.value.replace(/\D/g, '').slice(0, 7)))}
            />
          </Field>

          <label className="switch">
            <input
              type="checkbox"
              checked={form.active}
              onChange={(event) => setField('active', event.target.checked)}
            />
            <span>
              <strong>{form.active ? 'Ativo' : 'Inativo'}</strong>
              {form.active ? ' · aparece no caixa e pode ser vendido' : ' · fora de linha: não aparece no caixa'}
            </span>
          </label>
        </fieldset>

        <footer className="dialog__footer">
          <button type="button" className="btn btn--outline btn--lg" onClick={onClose} disabled={saving}>
            Cancelar <kbd>Esc</kbd>
          </button>
          <button type="submit" className="btn btn--primary btn--lg" disabled={saving}>
            {saving ? 'Salvando...' : isEditing ? 'Salvar alterações' : 'Cadastrar produto'}
          </button>
        </footer>
      </form>
    </dialog>
  )
}

// Um campo do formulário: rótulo, o input (children) e a mensagem de erro.
function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
  return (
    <label className={`field${error ? ' field--error' : ''}`}>
      <span>{label}</span>
      {children}
      {error && (
        <small className="field-error" role="alert">
          {error}
        </small>
      )}
    </label>
  )
}
