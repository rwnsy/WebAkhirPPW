const html = document.documentElement;
const savedTheme = localStorage.getItem("pustakata-theme");

if (savedTheme) {
    html.setAttribute("data-theme", savedTheme);
}

const themeToggle = document.getElementById("themeToggle");
themeToggle?.addEventListener("click", () => {
    const current = html.getAttribute("data-theme");
    const next = current === "dark" ? "light" : "dark";

    html.setAttribute("data-theme", next);
    localStorage.setItem("pustakata-theme", next);
    showToast(`Tema ${next === "dark" ? "gelap" : "terang"} aktif.`, "success");
});

const navToggle = document.getElementById("navToggle");
const navMenu = document.getElementById("navMenu");
const navActions = document.querySelector(".nav-actions");

navToggle?.addEventListener("click", () => {
    const isOpen = navMenu?.classList.toggle("show") || false;
    navActions?.classList.toggle("show", isOpen);
    navToggle.setAttribute("aria-expanded", String(isOpen));
});

const initReveal = () => {
    const revealSelector = ".reveal, .reveal-up, .reveal-left, .reveal-right, .reveal-scale";
    const revealTargets = Array.from(document.querySelectorAll(revealSelector));

    if (revealTargets.length === 0) {
        return;
    }

    const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    document.querySelectorAll("[data-reveal-stagger]").forEach((group) => {
        const step = Number(group.dataset.revealStagger) || 55;
        const maxDelay = Number(group.dataset.revealMaxDelay) || 300;

        group.querySelectorAll(revealSelector).forEach((item, index) => {
            if (!item.style.getPropertyValue("--reveal-delay")) {
                item.style.setProperty("--reveal-delay", `${Math.min(index * step, maxDelay)}ms`);
            }
        });
    });

    revealTargets.forEach((item, index) => {
        if (!item.style.getPropertyValue("--reveal-delay")) {
            item.style.setProperty("--reveal-delay", `${Math.min(index * 35, 220)}ms`);
        }

        if (item.dataset.revealDelay) {
            item.style.setProperty("--reveal-delay", `${Number(item.dataset.revealDelay) || 0}ms`);
        }
    });

    if (prefersReducedMotion || !("IntersectionObserver" in window)) {
        revealTargets.forEach((item) => item.classList.add("is-visible"));
    } else {
        html.classList.add("reveal-enabled");

        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add("is-visible");
                observer.unobserve(entry.target);
            });
        }, {
            root: null,
            rootMargin: "0px 0px -40px 0px",
            threshold: 0.12
        });

        revealTargets.forEach((item) => revealObserver.observe(item));
    }
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initReveal, { once: true });
} else {
    initReveal();
}

function showToast(message, type = "success") {
    if (!message) {
        return;
    }

    let stack = document.querySelector(".toast-stack");
    if (!stack) {
        stack = document.createElement("div");
        stack.className = "toast-stack";
        document.body.appendChild(stack);
    }

    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    stack.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add("show"));

    setTimeout(() => {
        toast.classList.remove("show");
        setTimeout(() => toast.remove(), 220);
    }, 3600);
}

document.querySelectorAll(".flash-message").forEach((message) => {
    const type = message.classList.contains("alert-success")
        ? "success"
        : (message.classList.contains("alert-warning") ? "warning" : "error");
    showToast(message.textContent.trim(), type);
});

function confirmModal(message, onConfirm) {
    let modal = document.querySelector(".confirm-modal");
    if (!modal) {
        modal = document.createElement("div");
        modal.className = "confirm-modal";
        modal.innerHTML = `
            <div class="confirm-dialog" role="dialog" aria-modal="true">
                <h2>Konfirmasi</h2>
                <p></p>
                <div class="confirm-actions">
                    <button type="button" class="btn btn-outline" data-confirm-cancel>Batal</button>
                    <button type="button" class="btn btn-primary" data-confirm-ok>Lanjutkan</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    modal.querySelector("p").textContent = message || "Lanjutkan aksi ini?";
    modal.classList.add("show");

    const cancel = modal.querySelector("[data-confirm-cancel]");
    const ok = modal.querySelector("[data-confirm-ok]");

    const close = () => modal.classList.remove("show");
    const confirm = () => {
        close();
        onConfirm();
    };

    cancel.onclick = close;
    ok.onclick = confirm;
    modal.onclick = (event) => {
        if (event.target === modal) {
            close();
        }
    };
}

document.querySelectorAll("[data-password-toggle]").forEach((button) => {
    button.addEventListener("click", () => {
        const field = button.closest(".password-field");
        const input = field?.querySelector("input");

        if (!input) {
            return;
        }

        const visible = input.type === "text";
        input.type = visible ? "password" : "text";
        button.textContent = visible ? "Tampil" : "Sembunyi";
    });
});

document.querySelectorAll("[data-image-preview]").forEach((input) => {
    input.addEventListener("change", () => {
        const preview = document.querySelector(input.dataset.imagePreview);
        const file = input.files?.[0];

        if (!preview || !file) {
            return;
        }

        if (preview.dataset.previewUrl) {
            URL.revokeObjectURL(preview.dataset.previewUrl);
            delete preview.dataset.previewUrl;
        }

        const allowedImageTypes = ["image/jpeg", "image/png", "image/webp", "image/avif"];
        const allowedImageExtensions = ["jpg", "jpeg", "png", "webp", "avif"];
        const fileExtension = file.name.split(".").pop()?.toLowerCase() || "";

        if (!allowedImageTypes.includes(file.type) && !allowedImageExtensions.includes(fileExtension)) {
            input.value = "";
            preview.classList.add("is-hidden");
            showToast("Format cover harus JPG, PNG, WEBP, atau AVIF.", "error");
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            input.value = "";
            preview.classList.add("is-hidden");
            showToast("Ukuran cover maksimal 2 MB.", "error");
            return;
        }

        const previewUrl = URL.createObjectURL(file);
        preview.dataset.previewUrl = previewUrl;
        preview.src = previewUrl;
        preview.classList.remove("is-hidden");
        showToast(`Preview cover "${file.name}" siap ditinjau.`, "success");
    });
});

document.querySelectorAll("[data-character-counter]").forEach((field) => {
    const counter = document.querySelector(field.dataset.characterCounter);
    const maxLength = Number(field.getAttribute("maxlength")) || 0;

    const updateCounter = () => {
        if (!counter) {
            return;
        }

        counter.textContent = `${field.value.length}/${maxLength || "∞"} karakter`;
        counter.classList.toggle("is-warning", maxLength > 0 && field.value.length > maxLength * 0.86);
    };

    field.addEventListener("input", updateCounter);
    updateCounter();
});

function validateForm(form) {
    let isValid = true;

    form.querySelectorAll("input[required], textarea[required], select[required]").forEach((field) => {
        field.classList.remove("field-invalid");

        if (!field.checkValidity()) {
            field.classList.add("field-invalid");
            isValid = false;
        }
    });

    if (!isValid) {
        showToast("Periksa kembali field yang wajib diisi.", "error");
    }

    return isValid;
}

document.addEventListener("input", (event) => {
    const field = event.target;

    if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) {
        return;
    }

    if (field.checkValidity()) {
        field.classList.remove("field-invalid");
    }
});

document.querySelectorAll("[data-search-feedback]").forEach((form) => {
    form.addEventListener("submit", () => {
        const query = form.querySelector("input[name='q']")?.value.trim();

        if (query) {
            showToast(`Mencari "${query}" di katalog...`, "success");
        } else {
            showToast("Menampilkan semua koleksi buku.", "success");
        }
    });
});

document.querySelectorAll("[data-filter-feedback]").forEach((form) => {
    form.addEventListener("submit", () => {
        const selected = form.querySelector("input[name='kategori']:checked")?.value.trim();
        showToast(selected ? `Memfilter kategori "${selected}"...` : "Menampilkan semua genre.", "success");
    });

    form.querySelectorAll("input[name='kategori']").forEach((input) => {
        input.addEventListener("change", () => {
            showToast(input.value ? `Kategori "${input.value}" dipilih.` : "Filter genre direset.", "success");
        });
    });
});

document.addEventListener("submit", (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (event.defaultPrevented) {
        return;
    }

    const submitter = event.submitter;
    const confirmMessage = submitter?.dataset.confirm || form.dataset.confirm;

    if (form.hasAttribute("data-validate") && !validateForm(form)) {
        event.preventDefault();
        return;
    }

    if (confirmMessage && form.dataset.confirmed !== "true") {
        event.preventDefault();
        confirmModal(confirmMessage, () => {
            form.dataset.confirmed = "true";

            if (submitter?.name) {
                const hidden = document.createElement("input");
                hidden.type = "hidden";
                hidden.name = submitter.name;
                hidden.value = submitter.value;
                form.appendChild(hidden);
            }

            form.submit();
        });
        return;
    }

    if (submitter && submitter.tagName === "BUTTON") {
        form.classList.add("is-submitting");
        submitter.classList.add("is-loading");
        submitter.disabled = true;
        submitter.dataset.originalText = submitter.textContent;
        submitter.textContent = "Memproses...";
    }
});

const scrollTopButton = document.createElement("button");
scrollTopButton.type = "button";
scrollTopButton.className = "scroll-top-btn";
scrollTopButton.setAttribute("aria-label", "Kembali ke atas");
scrollTopButton.textContent = "↑";
document.body.appendChild(scrollTopButton);

let scrollTicking = false;
const updateScrollTopButton = () => {
    scrollTopButton.classList.toggle("show", window.scrollY > 520);
    scrollTicking = false;
};

window.addEventListener("scroll", () => {
    if (scrollTicking) {
        return;
    }

    scrollTicking = true;
    requestAnimationFrame(updateScrollTopButton);
}, { passive: true });

scrollTopButton.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
});
