export default defineNuxtRouteMiddleware(() => {
  if (import.meta.client) {
    const token = localStorage.getItem('auth_token')
    const level = localStorage.getItem('user_level')
    if (!token) return navigateTo('/login')
    if (level !== 'petugas' && level !== 'admin') return navigateTo('/login')
  }
})
