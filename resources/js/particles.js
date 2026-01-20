/**
 * Particle Effect System - Shooting Stars
 * Bintang jatuh diagonal dengan trail panjang
 */

class ParticleSystem {
    static instance = null;

    constructor(canvasId, options = {}) {
        // Destroy existing instance
        if (ParticleSystem.instance) {
            ParticleSystem.instance.destroy();
        }

        this.canvasId = canvasId;
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;

        this.ctx = this.canvas.getContext('2d', { alpha: true, desynchronized: true });
        this.particles = [];
        this.animationId = null;
        this.resizeHandler = null;

        // Detect device performance
        this.isLowPerformance = this.detectPerformance();

        // Adjust settings based on device - more aggressive reduction for mobile
        const particleCount = this.isMobile ? 15 : (this.isLowPerformance ? 20 : (options.particleCount || 40));
        const trailLength = this.isMobile ? 3 : (this.isLowPerformance ? 5 : 10);

        // Fixed options - tidak boleh berubah
        this.options = {
            particleCount: particleCount,
            particleColor: options.particleColor || 'rgba(255, 215, 0, 0.7)',
            particleSize: { min: 1.5, max: 3 }, // Fixed size
            shapes: options.shapes || ['star'],
            glow: options.glow !== false && !this.isLowPerformance, // Disable glow on low-end
            trailLength: trailLength
        };

        ParticleSystem.instance = this;
        this.init();
    }

    detectPerformance() {
        // Check for low-end device indicators
        this.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const cores = navigator.hardwareConcurrency || 2;
        const memory = navigator.deviceMemory || 4;

        // Low performance if mobile with few cores or low memory
        return this.isMobile && (cores <= 4 || memory <= 4);
    }

    init() {
        // Only add resize listener on desktop to prevent scroll/touch interference
        if (!this.isMobile) {
            this.resizeHandler = () => this.resizeCanvas();
            window.addEventListener('resize', this.resizeHandler);
        }

        // Stop any existing animation first
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }

        // Clear canvas completely
        if (this.ctx && this.canvas) {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        }

        this.resizeCanvas();
        this.createParticles();
        this.animate();
    }

    resizeCanvas() {
        const parent = this.canvas.parentElement;
        if (!parent) return;

        // Stop animation during resize
        const wasAnimating = !!this.animationId;
        if (wasAnimating) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }

        // For absolute positioned parent, get dimensions from its parent container
        const rect = parent.getBoundingClientRect();
        const width = Math.floor(rect.width || parent.clientWidth || parent.offsetWidth);
        const height = Math.floor(rect.height || parent.clientHeight || parent.offsetHeight);

        // Only resize if dimensions are valid and different
        if (width > 0 && height > 0) {
            // Set canvas internal dimensions
            this.canvas.width = width;
            this.canvas.height = height;

            // Remove any conflicting inline styles
            this.canvas.style.width = '100%';
            this.canvas.style.height = '100%';
            this.canvas.style.display = 'block';
        }

        // Clear canvas after resize
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Recreate particles with fresh state
        this.createParticles();

        // Resume animation
        if (wasAnimating) {
            this.animate();
        }
    }

    createParticles() {
        this.particles = [];
        for (let i = 0; i < this.options.particleCount; i++) {
            this.particles.push(this.createParticle());
        }
    }

    createParticle() {
        // Fixed angle and velocity
        const angle = 45;
        const angleRad = (angle * Math.PI) / 180;
        const size = 2.25; // Fixed size (average of 1.5 and 3)
        const velocityX = 3.0; // Fixed velocity
        const velocityY = 3.0; // Fixed velocity

        return {
            x: Math.random() * (this.canvas.width + 300) - 200,
            y: Math.random() * this.canvas.height - this.canvas.height - 100,
            size: size,
            opacity: Math.random() * 0.2 + 0.6,
            shape: this.options.shapes[Math.floor(Math.random() * this.options.shapes.length)],
            velocityX: Math.cos(angleRad) * velocityX,
            velocityY: Math.sin(angleRad) * velocityY,
            trail: [],
            trailLength: this.options.trailLength
        };
    }

    updateParticle(particle) {
        particle.trail.unshift({ x: particle.x, y: particle.y });
        if (particle.trail.length > particle.trailLength) {
            particle.trail.pop();
        }

        particle.x += particle.velocityX;
        particle.y += particle.velocityY;

        if (particle.y > this.canvas.height + 50 || particle.x > this.canvas.width + 50) {
            particle.y = Math.random() * -300 - 100;
            particle.x = Math.random() * (this.canvas.width * 0.6) - 200;
            particle.trail = [];
            particle.opacity = Math.random() * 0.2 + 0.6;
        }
    }

    drawParticle(particle) {
        // Draw trail with optimized rendering
        if (particle.trail.length > 1) {
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';

            // Draw trail in single path for better performance
            for (let i = 0; i < particle.trail.length - 1; i++) {
                const current = particle.trail[i];
                const next = particle.trail[i + 1];
                const fadeRatio = 1 - i / particle.trail.length;

                const trailOpacity = particle.opacity * fadeRatio * 0.6;
                const trailSize = particle.size * (0.2 + fadeRatio * 0.4);

                // Use solid color instead of gradient for low-end devices
                if (this.isLowPerformance) {
                    this.ctx.strokeStyle = this.options.particleColor.replace('0.7', trailOpacity);
                } else {
                    const gradient = this.ctx.createLinearGradient(
                        current.x, current.y,
                        next.x, next.y
                    );
                    gradient.addColorStop(0, this.options.particleColor.replace('0.7', trailOpacity));
                    gradient.addColorStop(1, this.options.particleColor.replace('0.7', trailOpacity * 0.5));
                    this.ctx.strokeStyle = gradient;
                }

                this.ctx.lineWidth = trailSize * 1.5;

                if (this.options.glow) {
                    this.ctx.shadowBlur = 6 * fadeRatio;
                    this.ctx.shadowColor = this.options.particleColor;
                }

                this.ctx.beginPath();
                this.ctx.moveTo(current.x, current.y);
                this.ctx.lineTo(next.x, next.y);
                this.ctx.stroke();
            }
        }

        // Draw main particle
        this.ctx.fillStyle = this.options.particleColor.replace('0.7', particle.opacity);

        if (this.options.glow) {
            this.ctx.shadowBlur = 10;
            this.ctx.shadowColor = this.options.particleColor;
        }

        if (particle.shape === 'star') {
            this.drawStar(particle);
        } else if (particle.shape === 'diamond') {
            this.drawDiamond(particle);
        } else {
            this.drawCircle(particle);
        }
        this.ctx.shadowBlur = 0;
    }

    drawCircle(particle) {
        this.ctx.beginPath();
        this.ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
        this.ctx.fill();
    }

    drawStar(particle) {
        const spikes = 5;
        const outerRadius = particle.size;
        const innerRadius = particle.size / 2;
        let rot = (Math.PI / 2) * 3;
        const step = Math.PI / spikes;

        this.ctx.save();
        this.ctx.translate(particle.x, particle.y);
        this.ctx.rotate(-45 * Math.PI / 180);
        this.ctx.beginPath();
        this.ctx.moveTo(0, -outerRadius);

        for (let i = 0; i < spikes; i++) {
            this.ctx.lineTo(Math.cos(rot) * outerRadius, Math.sin(rot) * outerRadius);
            rot += step;
            this.ctx.lineTo(Math.cos(rot) * innerRadius, Math.sin(rot) * innerRadius);
            rot += step;
        }

        this.ctx.lineTo(0, -outerRadius);
        this.ctx.closePath();
        this.ctx.fill();
        this.ctx.restore();
    }

    drawDiamond(particle) {
        this.ctx.save();
        this.ctx.translate(particle.x, particle.y);
        this.ctx.rotate(-45 * Math.PI / 180);
        this.ctx.beginPath();
        this.ctx.moveTo(0, -particle.size);
        this.ctx.lineTo(particle.size, 0);
        this.ctx.lineTo(0, particle.size);
        this.ctx.lineTo(-particle.size, 0);
        this.ctx.closePath();
        this.ctx.fill();
        this.ctx.restore();
    }

    animate() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        this.particles.forEach(particle => {
            this.updateParticle(particle);
            this.drawParticle(particle);
        });

        this.animationId = requestAnimationFrame(() => this.animate());
    }

    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }

        if (this.resizeHandler) {
            window.removeEventListener('resize', this.resizeHandler);
            this.resizeHandler = null;
        }

        this.particles = [];

        if (this.ctx && this.canvas) {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        }

        // Clear singleton reference
        if (ParticleSystem.instance === this) {
            ParticleSystem.instance = null;
        }
    }
}

window.ParticleSystem = ParticleSystem;
export default ParticleSystem;
