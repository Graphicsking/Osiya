import test from 'node:test'
import assert from 'node:assert/strict'
import { readdirSync, readFileSync, existsSync } from 'node:fs'
import { pathToFileURL } from 'node:url'
import { Window } from 'happy-dom'

const window = new Window({ url: 'http://localhost:5173' })
const runtimeErrors = []
const originalConsoleError = console.error
console.error = (...args) => runtimeErrors.push(args.map(String).join(' '))
for (const name of ['window', 'document', 'navigator', 'HTMLElement', 'HTMLInputElement', 'SVGElement', 'Element', 'Node', 'Document', 'DocumentFragment', 'ShadowRoot', 'Event', 'KeyboardEvent', 'MouseEvent', 'CustomEvent', 'localStorage']) {
  Object.defineProperty(globalThis, name, { configurable: true, value: name === 'window' ? window : window[name] })
}
globalThis.requestAnimationFrame = window.requestAnimationFrame.bind(window)
globalThis.cancelAnimationFrame = window.cancelAnimationFrame.bind(window)
window.document.body.innerHTML = '<div id="app"></div>'
const entry = readdirSync(new URL('../dist/assets/', import.meta.url)).find(name => name.endsWith('.js'))
await import(pathToFileURL(new URL(`../dist/assets/${entry}`, import.meta.url).pathname).href)
const settle = () => new Promise(resolve => setTimeout(resolve, 0))
const click = async element => { assert.ok(element, 'Expected control to exist'); element.click(); await settle() }
const keyboard = async (target, key, options = {}) => { target.dispatchEvent(new window.KeyboardEvent('keydown', { key, bubbles: true, cancelable: true, ...options })); await settle() }

test('campaign identity, assets and navigation render in the production bundle', () => {
  assert.match(document.title || readFileSync(new URL('../dist/index.html', import.meta.url), 'utf8'), /Arthur Amug Osiya/)
  assert.match(document.querySelector('.hero .eyebrow').textContent, /ARTHUR AMUG OSIYA/)
  for (const image of document.querySelectorAll('img')) {
    assert.ok(image.alt.trim())
    assert.ok(existsSync(new URL(`../dist${image.getAttribute('src')}`, import.meta.url)), `Image exists: ${image.src}`)
  }
  for (const anchor of document.querySelectorAll('a[href^="#"]')) assert.ok(document.querySelector(anchor.getAttribute('href')), `Target exists: ${anchor.hash}`)
})

test('priorities change content and support arrow-key navigation', async () => {
  await click(document.getElementById('priority-tab-1'))
  assert.equal(document.getElementById('priority-tab-1').getAttribute('aria-selected'), 'true')
  assert.match(document.querySelector('[role="tabpanel"]').textContent, /Better care/)
  await keyboard(document.getElementById('priority-tab-1'), 'ArrowDown')
  assert.equal(document.activeElement.id, 'priority-tab-2')
  assert.match(document.querySelector('[role="tabpanel"]').textContent, /Open doors/)
  await keyboard(document.getElementById('priority-tab-2'), 'ArrowUp')
  assert.equal(document.activeElement.id, 'priority-tab-1')
})

test('mobile navigation closes when a section is selected', async () => {
  await click(document.querySelector('.menu-toggle'))
  assert.equal(document.querySelector('.menu-toggle').getAttribute('aria-expanded'), 'true')
  await click(document.querySelector('#mobile-nav a'))
  assert.equal(document.querySelector('#mobile-nav'), null)
  assert.equal(document.querySelector('.menu-toggle').getAttribute('aria-expanded'), 'false')
})

test('static hero keeps its background and its campaign call to action opens the interest form', async () => {
  const hero = document.querySelector('#home')
  const image = hero.querySelector('img')
  assert.equal(hero.querySelectorAll('img').length, 1)
  assert.equal(hero.querySelector('[aria-roledescription="carousel"]'), null)
  const source = image.getAttribute('src')
  await keyboard(hero, 'ArrowRight')
  assert.equal(image.getAttribute('src'), source)
  await click(hero.querySelector('.campaign-secondary'))
  assert.ok(document.querySelector('.interest-dialog'))
  await keyboard(document, 'Escape')
  assert.equal(document.querySelector('.interest-dialog'), null)
})

test('gallery filters and show-more retain the correct photo when opened', async () => {
  assert.equal(document.querySelectorAll('.gallery-card').length, 3)
  await click(document.querySelector('.gallery-more button'))
  assert.equal(document.querySelectorAll('.gallery-card').length, 5)
  assert.equal(document.activeElement, document.querySelectorAll('.gallery-card')[3])
  const filter = label => [...document.querySelectorAll('.gallery-filters button')].find(button => button.textContent.startsWith(label))
  await click(filter('Community'))
  assert.equal(document.querySelectorAll('.gallery-card').length, 2)
  assert.equal(document.querySelector('.gallery-more'), null)
  await click(filter('Leadership'))
  assert.equal(document.querySelectorAll('.gallery-card').length, 3)
  const expectedPhoto = document.querySelector('.gallery-card img').getAttribute('src')
  await click(document.querySelector('.gallery-card'))
  assert.equal(document.querySelector('.lightbox figure > img').getAttribute('src'), expectedPhoto)
  await click(document.querySelectorAll('.lightbox-thumbs button')[4])
  assert.match(document.querySelector('.lightbox figcaption').textContent, /5 \/ 5/)
  await keyboard(document, 'Escape')
  await click(filter('All moments'))
})

test('questions expand, collapse, and lead into the interest form', async () => {
  assert.equal(document.getElementById('question-0').getAttribute('aria-expanded'), 'true')
  await click(document.getElementById('question-3'))
  assert.equal(document.getElementById('question-0').getAttribute('aria-expanded'), 'false')
  assert.equal(document.getElementById('question-3').getAttribute('aria-expanded'), 'true')
  await click(document.querySelector('#answer-3 button'))
  assert.ok(document.querySelector('.interest-dialog'))
  await keyboard(document, 'Escape')
  await click(document.getElementById('question-3'))
  assert.equal(document.getElementById('question-3').getAttribute('aria-expanded'), 'false')
})

test('gallery opens, wraps between photos, traps focus, and restores its trigger', async () => {
  const trigger = document.querySelector('.gallery-card')
  trigger.focus(); await click(trigger)
  assert.equal(document.activeElement.getAttribute('aria-label'), 'Close gallery')
  assert.equal(document.body.style.overflow, 'hidden')
  await keyboard(document, 'ArrowLeft')
  assert.match(document.querySelector('.lightbox figcaption').textContent, /5 \/ 5/)
  await keyboard(document.activeElement, 'Tab', { shiftKey: true })
  assert.equal(document.activeElement.getAttribute('aria-label'), 'Next photo')
  await keyboard(document, 'Escape')
  assert.equal(document.querySelector('.lightbox'), null)
  assert.equal(document.activeElement, trigger)
  assert.equal(document.body.style.overflow, '')
})

test('interest form saves locally, explains delivery, restores a draft and removes it', async () => {
  const trigger = document.querySelector('.nav-cta')
  trigger.focus(); await click(trigger)
  assert.equal(document.activeElement.name, 'name')
  const enter = (selector, value) => { const input = document.querySelector(selector); input.value = value; input.dispatchEvent(new window.Event('input', { bubbles: true })) }
  enter('input[name="name"]', 'Demo Visitor')
  enter('input[name="email"]', 'demo@example.com')
  await click(document.querySelector('input[type="checkbox"]'))
  document.querySelector('.interest-dialog form').dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }))
  await settle()
  assert.match(document.querySelector('#interest-privacy').textContent, /campaign has not received it/)
  assert.equal(JSON.parse(localStorage.getItem('busia2027-interest-draft')).name, 'Demo Visitor')
  await keyboard(document, 'Escape')
  assert.equal(document.activeElement, trigger)
  await click(trigger)
  assert.equal(document.querySelector('input[name="name"]').value, 'Demo Visitor')
  await click(document.querySelector('.clear-draft'))
  assert.equal(localStorage.getItem('busia2027-interest-draft'), null)
  assert.equal(document.querySelector('input[name="email"]').value, '')
  await keyboard(document, 'Escape')
  assert.equal(document.querySelector('[role="dialog"]'), null)
})

test.after(() => {
  window.happyDOM.abort()
  console.error = originalConsoleError
  assert.deepEqual(runtimeErrors, [], 'Production bundle should not emit runtime errors')
})
