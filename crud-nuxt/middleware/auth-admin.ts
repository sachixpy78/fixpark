export default defineNuxtRouteMiddleware(() => {
  if (import.meta.client) {
    const token = localStorage.getItem('auth_token')
    const level = localStorage.getItem('user_level')
    if (!token || level !== 'admin') return navigateTo('/login')
  }
})
