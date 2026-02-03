export function gravatarUrl(email: string, size = 80) {
  const normalized = email.trim().toLowerCase()
  const hash = md5(normalized)
  return `https://www.gravatar.com/avatar/${hash}?s=${size}&d=identicon`
}

// Minimal MD5 implementation for Gravatar hashing.
function md5(input: string) {
  let h0 = 0x67452301
  let h1 = 0xefcdab89
  let h2 = 0x98badcfe
  let h3 = 0x10325476

  const bytes = toUtf8Bytes(input)
  const len = bytes.length
  bytes.push(0x80)
  while ((bytes.length % 64) !== 56) bytes.push(0)

  const bitLen = len * 8
  for (let i = 0; i < 8; i++) bytes.push((bitLen >>> (i * 8)) & 0xff)

  for (let i = 0; i < bytes.length; i += 64) {
    const w: number[] = Array.from({ length: 16 })
    for (let j = 0; j < 16; j++) {
      const k = i + j * 4
      const b0 = bytes[k] ?? 0
      const b1 = bytes[k + 1] ?? 0
      const b2 = bytes[k + 2] ?? 0
      const b3 = bytes[k + 3] ?? 0
      w[j] = b0 | (b1 << 8) | (b2 << 16) | (b3 << 24)
    }

    let a = h0
    let b = h1
    let c = h2
    let d = h3

    for (let j = 0; j < 64; j++) {
      let f = 0
      let g = 0

      if (j < 16) {
        f = (b & c) | (~b & d)
        g = j
      } else if (j < 32) {
        f = (d & b) | (~d & c)
        g = (5 * j + 1) % 16
      } else if (j < 48) {
        f = b ^ c ^ d
        g = (3 * j + 5) % 16
      } else {
        f = c ^ (b | ~d)
        g = (7 * j) % 16
      }

      const tmp = d
      d = c
      c = b
      const rotate = S[j] ?? 0
      const sum = (a + f + (K[j] ?? 0) + (w[g] ?? 0)) >>> 0
      b = (b + leftRotate(sum, rotate)) >>> 0
      a = tmp
    }

    h0 = (h0 + a) >>> 0
    h1 = (h1 + b) >>> 0
    h2 = (h2 + c) >>> 0
    h3 = (h3 + d) >>> 0
  }

  return [h0, h1, h2, h3].map(toHexLE).join('')
}

function toUtf8Bytes(str: string) {
  const bytes: number[] = []
  for (let i = 0; i < str.length; i++) {
    let code = str.charCodeAt(i)
    if (code < 0x80) {
      bytes.push(code)
    } else if (code < 0x800) {
      bytes.push(0xc0 | (code >> 6), 0x80 | (code & 0x3f))
    } else if (code < 0xd800 || code >= 0xe000) {
      bytes.push(0xe0 | (code >> 12), 0x80 | ((code >> 6) & 0x3f), 0x80 | (code & 0x3f))
    } else {
      i++
      const next = str.charCodeAt(i) || 0
      code = 0x10000 + (((code & 0x3ff) << 10) | (next & 0x3ff))
      bytes.push(
        0xf0 | (code >> 18),
        0x80 | ((code >> 12) & 0x3f),
        0x80 | ((code >> 6) & 0x3f),
        0x80 | (code & 0x3f)
      )
    }
  }
  return bytes
}

function leftRotate(x: number, c: number) {
  return (x << c) | (x >>> (32 - c))
}

function toHexLE(num: number) {
  const hex = []
  for (let i = 0; i < 4; i++) {
    hex.push(((num >> (i * 8)) & 0xff).toString(16).padStart(2, '0'))
  }
  return hex.join('')
}

const S = [
  7, 12, 17, 22, 7, 12, 17, 22, 7, 12, 17, 22, 7, 12, 17, 22,
  5, 9, 14, 20, 5, 9, 14, 20, 5, 9, 14, 20, 5, 9, 14, 20,
  4, 11, 16, 23, 4, 11, 16, 23, 4, 11, 16, 23, 4, 11, 16, 23,
  6, 10, 15, 21, 6, 10, 15, 21, 6, 10, 15, 21, 6, 10, 15, 21,
]

const K = Array.from({ length: 64 }, (_, i) => Math.floor(Math.abs(Math.sin(i + 1)) * 2 ** 32))
