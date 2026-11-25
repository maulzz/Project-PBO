export class ApiError extends Error {
  constructor(message, status = 500, errors = null) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }

  getFirstValidationError() {
    if (!this.errors || typeof this.errors !== 'object') return null
    const firstFieldErrors = Object.values(this.errors)[0]
    return Array.isArray(firstFieldErrors) ? (firstFieldErrors[0] ?? null) : null
  }
}

export class HttpClient {
  #baseUrl

  constructor(baseUrl) {
    this.#baseUrl = HttpClient.normalizeBaseUrl(baseUrl)
  }

  static normalizeBaseUrl(url) {
    const raw = (url || 'https://project-pbo.maulzzserver.my.id/api/v1/').trim()
    return raw.replace(/\/+$/, '')
  }

  get baseUrl() {
    return this.#baseUrl
  }

  buildUrl(path, query = null) {
    const normalizedPath = `/${String(path).replace(/^\/+/, '')}`
    const fullUrl = `${this.#baseUrl}${normalizedPath}`

    if (!query || typeof query !== 'object') {
      return fullUrl
    }

    const params = new URLSearchParams()
    for (const [key, value] of Object.entries(query)) {
      if (value !== null && value !== undefined && value !== '') {
        params.set(key, String(value))
      }
    }

    const queryString = params.toString()
    return queryString ? `${fullUrl}?${queryString}` : fullUrl
  }

  async request(path, { method = 'GET', body = undefined, query = null, headers = {} } = {}) {
    const url = this.buildUrl(path, query)
    const response = await fetch(url, {
      method,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...headers,
      },
      body: body !== undefined ? JSON.stringify(body) : undefined,
    })

    const json = await response.json().catch(() => ({}))

    if (!response.ok) {
      throw new ApiError(
        json.message ?? 'Terjadi kesalahan pada server, silakan coba lagi.',
        response.status,
        json.errors ?? null,
      )
    }

    return json.data ?? json
  }

  get(path, query = null) {
    return this.request(path, { method: 'GET', query })
  }

  post(path, body = undefined) {
    return this.request(path, { method: 'POST', body })
  }
}
