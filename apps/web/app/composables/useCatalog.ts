import type { Catalog } from '~/types'

// Categorías, unidades, zonas y estados. Se piden una sola vez a GET /catalog.
export function useCatalog() {
  const catalog = useState<Catalog | null>('catalog', () => null)

  async function load() {
    if (!catalog.value) {
      catalog.value = await useApi().request<Catalog>('/catalog')
    }
    return catalog.value
  }

  function label(group: keyof Catalog, value: string): string {
    return catalog.value?.[group].find(item => item.value === value)?.label ?? value
  }

  return { catalog, load, label }
}