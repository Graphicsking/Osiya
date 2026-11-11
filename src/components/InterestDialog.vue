<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { X, ArrowUpRight, Check, Download } from '@lucide/vue'
const emit = defineEmits(['close'])
const dialog = ref(null)
const name = ref('')
const email = ref('')
const interest = ref('Volunteer')
const consent = ref(false)
const saved = ref(false)
const error = ref('')
let previousFocus
let previousOverflow
const storageKey = 'busia2027-interest-draft'
function saveInterest() {
  error.value = ''
  if (!name.value.trim() || !email.value.trim() || !consent.value) return
  try {
    localStorage.setItem(storageKey, JSON.stringify({ name: name.value.trim(), email: email.value.trim(), interest: interest.value }))
    saved.value = true
    nextTick(() => dialog.value.querySelector('h2')?.focus())
  } catch { error.value = 'Your browser could not save the draft. Please enable browser storage and try again.' }
}
function download() {
  const blob = new Blob([`BUSIA 2027 — INTEREST DRAFT\n\nName: ${name.value.trim()}\nEmail: ${email.value.trim()}\nInterest: ${interest.value}\n\nSaved locally. This draft has not been submitted to a campaign.\n`], { type: 'text/plain' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a'); link.href = url; link.download = 'busia-2027-interest.txt'; link.click(); URL.revokeObjectURL(url)
}
function clearDraft() { try { localStorage.removeItem(storageKey); name.value = ''; email.value = ''; consent.value = false; saved.value = false } catch { error.value = 'Unable to remove the saved draft from this browser.' } }
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
  previousFocus = document.activeElement; previousOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden'
  try { const draft = JSON.parse(localStorage.getItem(storageKey) || 'null'); if (draft) { name.value = draft.name || ''; email.value = draft.email || ''; interest.value = draft.interest || 'Volunteer' } } catch { /* Empty or unavailable storage keeps the form usable. */ }
  dialog.value.querySelector('input')?.focus(); document.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => { document.body.style.overflow = previousOverflow; document.removeEventListener('keydown', onKey); previousFocus?.focus() })
</script>
<template>
  <Teleport to="body"><div class="modal-backdrop" @click.self="emit('close')"><section ref="dialog" class="interest-dialog" role="dialog" aria-modal="true" aria-labelledby="interest-title" aria-describedby="interest-privacy"><button class="dialog-close" aria-label="Close interest form" @click="emit('close')"><X :size="22" /></button><span class="dialog-star">✳</span><div class="eyebrow">A SHARED FUTURE STARTS HERE</div><template v-if="!saved"><h2 id="interest-title">Bring your voice.<br /><span class="serif-word">Be part of it.</span></h2><p>Prepare your interest in the Busia 2027 journey.</p><form @submit.prevent="saveInterest"><label>Your name<input v-model="name" name="name" autocomplete="name" placeholder="Full name" maxlength="100" required /></label><label>Email address<input v-model="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" maxlength="254" required /></label><label>How would you like to get involved?<select v-model="interest" name="interest"><option>Volunteer</option><option>Share an idea</option><option>Community conversations</option><option>Campaign updates</option></select></label><label class="checkbox-label"><input v-model="consent" type="checkbox" required /><span>I agree to save these details on this device.</span></label><p id="interest-privacy" class="privacy-note">Website preview: your details stay in this browser. Nothing is sent to the campaign. You can remove your draft at any time.</p><p v-if="error" role="alert" class="form-error">{{ error }}</p><button class="button form-submit" type="submit">Save my interest draft <ArrowUpRight :size="18" /></button><button v-if="name || email" type="button" class="clear-draft" @click="clearDraft">Clear saved details</button></form></template><template v-else><span class="success-mark"><Check :size="27" /></span><h2 id="interest-title" tabindex="-1">Your voice<br /><span class="serif-word">matters.</span></h2><p id="interest-privacy">Your interest draft is saved on this device. The campaign has not received it. Download a copy to keep for later.</p><button class="button form-submit" @click="download">Download my draft <Download :size="18" /></button><button class="clear-draft" @click="clearDraft">Remove my saved details</button><button class="text-link dialog-done" @click="emit('close')">Back to the website <ArrowUpRight :size="16" /></button></template></section></div></Teleport>
</template>
