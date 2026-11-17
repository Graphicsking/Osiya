<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { X, Check } from '@lucide/vue'
import StarMark from './StarMark.vue'

const emit = defineEmits(['close'])
const dialog = ref(null)
const name = ref('')
const phone = ref('')
const interest = ref('Volunteer')
const consent = ref(false)
const saved = ref(false)
const submitting = ref(false)
const error = ref('')
let submissionId
let previousFocus
let previousOverflow
let requestController

async function submitInterest() {
  if (submitting.value || !name.value.trim() || !phone.value.trim() || !consent.value) return
  error.value = ''
  submitting.value = true
  requestController = new AbortController()
  const timeout = setTimeout(() => requestController.abort(), 15000)
  try {
    submissionId ||= crypto.randomUUID()
    const csrfResponse = await fetch('/api/csrf', { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: requestController.signal })
    if (!csrfResponse.ok) throw new Error('The form is temporarily unavailable. Please try again.')
    const { token } = await csrfResponse.json()
    if (!token) throw new Error('The form is temporarily unavailable. Please try again.')
    const response = await fetch('/api/interests', {
      method: 'POST', credentials: 'same-origin', signal: requestController.signal,
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify({ submission_id: submissionId, name: name.value.trim(), phone: phone.value.trim(), interest: interest.value, consent: consent.value }),
    })
    const result = await response.json().catch(() => ({}))
    if (!response.ok) {
      if (response.status === 422) throw new Error(Object.values(result.errors || {}).flat()[0] || 'Please check your details and try again.')
      if (response.status === 429) throw new Error('Too many attempts. Please wait a minute before trying again.')
      if (response.status === 419) throw new Error('Your session expired. Please submit the form again.')
      throw new Error('We could not save your details. Please try again.')
    }
    saved.value = true
    await nextTick()
    dialog.value?.querySelector('h2')?.focus()
  } catch (cause) {
    error.value = cause.name === 'AbortError' ? 'The request took too long. Please try again.' : cause.message === 'Failed to fetch' ? 'Please check your connection and try again.' : cause.message || 'We could not save your details. Please try again.'
  } finally {
    clearTimeout(timeout)
    submitting.value = false
  }
}

function onKey(event) {
  if (event.key === 'Escape') emit('close')
  if (event.key === 'Tab') {
    const elements = [...dialog.value.querySelectorAll('button, input, select, a[href]')].filter(el => !el.disabled)
    const first = elements[0]; const last = elements.at(-1)
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus() }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus() }
  }
}
onMounted(() => {
  previousFocus = document.activeElement
  previousOverflow = document.body.style.overflow
  document.body.style.overflow = 'hidden'
  dialog.value.querySelector('input')?.focus()
  document.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => {
  requestController?.abort()
  document.body.style.overflow = previousOverflow
  document.removeEventListener('keydown', onKey)
  previousFocus?.focus()
})
</script>

<template>
  <Teleport to="body">
    <div class="modal-backdrop" @click.self="emit('close')">
      <section ref="dialog" class="interest-dialog" role="dialog" aria-modal="true" aria-labelledby="interest-title" aria-describedby="interest-privacy">
        <button class="dialog-close" aria-label="Close interest form" @click="emit('close')"><X :size="22" /></button>
        <span class="dialog-star"><StarMark /></span>
        <div class="eyebrow">A SHARED FUTURE STARTS HERE</div>
        <template v-if="!saved">
          <h2 id="interest-title">Bring your voice.<br /><span class="serif-word">Be part of it.</span></h2>
          <p>Join the Busia 2027 journey.</p>
          <form @submit.prevent="submitInterest" :aria-busy="submitting">
            <label>Your name<input v-model="name" name="name" autocomplete="name" placeholder="Full name" maxlength="100" required :disabled="submitting" /></label>
            <label>Phone number<input v-model="phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" placeholder="0712 345 678" maxlength="32" required :disabled="submitting" /></label>
            <label>How would you like to get involved?<select v-model="interest" name="interest" :disabled="submitting"><option>Volunteer</option><option>Share an idea</option><option>Community conversations</option><option>Campaign updates</option></select></label>
            <label class="checkbox-label"><input v-model="consent" type="checkbox" required :disabled="submitting" /><span>I agree to be contacted by Busia 2027 about my involvement.</span></label>
            <p id="interest-privacy" class="privacy-note">Your name, phone number and involvement choice will be shared with the campaign team so they can follow up with you.</p>
            <p v-if="error" role="alert" class="form-error">{{ error }}</p>
            <button class="button form-submit" type="submit" :disabled="submitting">{{ submitting ? 'Submitting…' : 'Submit' }}</button>
          </form>
        </template>
        <template v-else>
          <span class="success-mark"><Check :size="27" /></span>
          <h2 id="interest-title" tabindex="-1">Your voice<br /><span class="serif-word">matters.</span></h2>
          <p id="interest-privacy" role="status">Thank you for getting involved. Your details have been received by the campaign team.</p>
          <button class="button form-submit" @click="emit('close')">Back to the website</button>
        </template>
      </section>
    </div>
  </Teleport>
</template>
