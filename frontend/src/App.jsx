import { useEffect, useState } from "react";
import QRCode from "qrcode";

const empty = { nama: "", whatsapp: "", kehadiran: "", tanggal: "", kilo: "" };
const phonePattern = /^(08\d{8,13}|62\d{9,14})$/;

export default function App() {
  const [form, setForm] = useState(empty);
  const [result, setResult] = useState(null);
  const [qr, setQr] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const [countdown, setCountdown] = useState("");
  const [customKilo, setCustomKilo] = useState("");

  useEffect(() => {
    const target = new Date("2026-10-22T00:00:00+07:00");
    const update = () => {
      const remaining = Math.max(0, target - new Date());
      const days = Math.floor(remaining / 86400000);
      const hours = Math.floor((remaining / 3600000) % 24);
      const minutes = Math.floor((remaining / 60000) % 60);
      const seconds = Math.floor((remaining / 1000) % 60);
      setCountdown(`${days} hari ${hours} jam ${minutes} menit ${seconds} detik`);
    };
    update();
    const timer = setInterval(update, 1000);
    return () => clearInterval(timer);
  }, []);

  async function submit(event) {
    event.preventDefault();
    setError("");
    if (!phonePattern.test(form.whatsapp))
      return setError("Gunakan format 08xxxxxxxxxx atau 62xxxxxxxxxx.");
    setBusy(true);
    try {
      const base = (import.meta.env.VITE_API_URL || "/api").replace(/\/$/, "");
      const response = await fetch(`${base}/submit.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ...form, kilo: form.kilo === "custom" ? customKilo : form.kilo }),
      });
      const body = await response.json();
      if (!response.ok) throw new Error(body.error || "Data gagal disimpan.");
      setResult(body.data);
      setQr(await QRCode.toDataURL(body.data.id, { width: 256, margin: 2 }));
    } catch (err) {
      setError(err.message || "Tidak dapat menghubungi server.");
    } finally {
      setBusy(false);
    }
  }

  function reset() {
    setForm(empty);
    setCustomKilo("");
    setResult(null);
    setQr("");
    setError("");
  }

  return (
    <main className="shell">
      <header>
        <div className="eyebrow">SAC AGRO FARM</div>

        {result ? (
          <h1 className="highlight">Terima kasih telah mengonfirmasi kehadiran.</h1>
        ) : (
          <>
            <h2 className="title-kicker">KONFIRMASI KEHADIRAN</h2>
            <h1 className="highlight">WISATA PETIK BUAH</h1>
            <div className="event-date">22—25 OKTOBER 2026</div>
          </>
        )}

        <p className="intro">
          {result ? "Simpan QR ini sebagai bukti registrasi." : "Siapkan kunjungan terbaik Anda."}
        </p>

        {!result && <div className="countdown" aria-label="Hitung mundur acara">
          <span>Menuju acara</span><strong>{countdown}</strong>
        </div>}
      </header>

      {result ? (
        <section className="result" aria-live="polite">
          <div className="success-badge">✓ Registrasi berhasil</div>

          <img className="qr" src={qr} alt={`QR registrasi ${result.id}`} />

          <h2>{result.nama}</h2>

          <p className="phone">{result.whatsapp}</p>

          <p className="attendance">
            {result.kehadiran === "hadir" ? "✓ Hadir" : "Tidak hadir"} · {result.tanggal}
          </p>

          <p className="attendance">Buah yang diambil · {result.kilo} kg</p>

          <p className="id">ID · {result.id}</p>

          <div className="actions">
            <button className="primary" onClick={() => window.print()}>
              Cetak QR
            </button>

            <button className="secondary" onClick={reset}>
              Kembali
            </button>
          </div>
        </section>
      ) : (
        <form onSubmit={submit}>
          {/* Benefit */}
          <div className="voucher-card">
            <div className="voucher-icon">★</div>

            <div className="voucher-content">
              <span className="voucher-label">BENEFIT KUNJUNGAN</span>

              <strong>VOUCHER 10%</strong>

              <span>Khusus untuk tamu yang melakukan konfirmasi</span>
            </div>

            <div className="voucher-notch top" />
            <div className="voucher-notch bottom" />
          </div>

          {/* Nama */}
          <div className="field">
            <label htmlFor="nama">Nama</label>

            <input
              id="nama"
              required
              maxLength="120"
              autoComplete="name"
              placeholder="Masukkan nama lengkap"
              value={form.nama}
              onChange={(e) =>
                setForm({
                  ...form,
                  nama: e.target.value,
                })
              }
            />
          </div>

          {/* WhatsApp */}
          <div className="field">
            <label htmlFor="whatsapp">No. WhatsApp</label>

            <input
              id="whatsapp"
              type="tel"
              inputMode="numeric"
              autoComplete="tel"
              required
              pattern="(08[0-9]{8,13}|62[0-9]{9,14})"
              title="Gunakan format 08xxxxxxxxxx atau 62xxxxxxxxxx"
              placeholder="081234567890"
              value={form.whatsapp}
              onChange={(e) =>
                setForm({
                  ...form,
                  whatsapp: e.target.value,
                })
              }
            />
          </div>

          {/* Tanggal hadir */}
          <div className="field">
            <label htmlFor="tanggal">Tanggal hadir</label>
            <input
              id="tanggal"
              type="date"
              required
              min="2026-10-22"
              max="2026-10-25"
              value={form.tanggal}
              onChange={(e) => setForm({ ...form, tanggal: e.target.value })}
            />
          </div>

          {/* Jumlah buah */}
          <div className="field">
            <label htmlFor="kilo">Perkiraan buah yang akan diambil</label>
            <div className="kilo-input">
              <select
                id="kilo"
                required
                value={form.kilo}
                onChange={(e) => {
                  setForm({ ...form, kilo: e.target.value });
                  if (e.target.value !== "custom") setCustomKilo("");
                }}
              >
                <option value="">Pilih jumlah</option>
                <option value="1">1 kg</option>
                <option value="2">2 kg</option>
                <option value="3">3 kg</option>
                <option value="5">5 kg</option>
                <option value="10">10 kg</option>
                <option value="custom">Jumlah lain</option>
              </select>
              {form.kilo === "custom" && (
                <input
                  type="number"
                  min="0.1"
                  max="100"
                  step="0.1"
                  required
                  placeholder="Masukkan kg"
                  onChange={(e) => setCustomKilo(e.target.value)}
                  value={customKilo}
                />
              )}
            </div>
          </div>

          {/* Kehadiran */}
          <fieldset>
            <legend>Konfirmasi kehadiran</legend>

            <div className="attendance-options">
              <label
                className={`attendance-card ${
                  form.kehadiran === "hadir" ? "selected" : ""
                }`}
              >
                <input
                  type="radio"
                  name="kehadiran"
                  value="hadir"
                  required
                  checked={form.kehadiran === "hadir"}
                  onChange={(e) =>
                    setForm({
                      ...form,
                      kehadiran: e.target.value,
                    })
                  }
                />

                <span className="radio-custom">✓</span>

                <span>
                  <strong>Hadir</strong>
                  <small>Saya akan hadir</small>
                </span>
              </label>

              <label
                className={`attendance-card ${
                  form.kehadiran === "tidak hadir" ? "selected" : ""
                }`}
              >
                <input
                  type="radio"
                  name="kehadiran"
                  value="tidak hadir"
                  checked={form.kehadiran === "tidak hadir"}
                  onChange={(e) =>
                    setForm({
                      ...form,
                      kehadiran: e.target.value,
                    })
                  }
                />

                <span className="radio-custom">✓</span>

                <span>
                  <strong>Tidak hadir</strong>
                  <small>Maaf, saya berhalangan</small>
                </span>
              </label>
            </div>
          </fieldset>

          {error && (
            <p className="error" role="alert">
              {error}
            </p>
          )}

          <button className="primary submit" disabled={busy}>
            {busy ? "Menyimpan…" : "✓  Kirim Konfirmasi"}
          </button>
        </form>
      )}

      <footer>
        🔒 Data Anda aman dan hanya digunakan untuk keperluan acara.
      </footer>
    </main>
  );
}
