/**
 * Particle Effect System
 * Creates falling particles with various effects
 */

class ParticleSystem {
    static instances = new Map();

    constructor(canvasId, options = {}) {
        // Prevent multiple instances for same canvas
        if (ParticleSystem.instances.has(canvasId)) {
            return ParticleSystem.instances.get(canvasId);
        }

        this.canvasId = canvasId;
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;

        this.ctx = this.canvas.getContext('2d');
        this.particles = [];
        this.animationId = null;

        // Default options
        this.options = {
            particleCount: options.particleCount || 50,
            particleColor: options.particleColor || 'rgba(255, 215, 0, 0.8)',
            particleSize: options.particleSize || { min: 2, max: 5 },
            speed: options.speed || { min: 1, max: 3 },
            shapes: options.shapes || ['circle', 'star', 'diamond'],
            glow: options.glow !== false,
            ...options
        };

        ParticleSystem.instances.set(canvasId, this);
        this.init();
    }

    init() {
        this.resizeCanvas();
        this.createParticles();
        this.animate();

        // Handle window resize
        window.addEventListener('resize', () => this.resizeCanvas());
    }

    resizeCanvas() {
        const parent = this.canvas.parentElement;
        this.canvas.width = parent.offsetWidth;
        this.canvas.height = parent.offsetHeight;
    }

    createParticles() {
        this.particles = [];
        for (let i = 0; i < this.options.particleCount; i++) {
            this.particles.push(this.createParticle());
        }
    }

    createParticle() {
        const shapes = this.options.shapes;
        return {
            x: Math.random() * this.canvas.width,
            y: Math.random() * this.canvas.height - this.canvas.height,
            size: Math.random() * (this.options.particleSize.max - this.options.particleSize.min) + this.options.particleSize.min,
            speed: Math.random() * (this.options.speed.max - this.options.speed.min) + this.options.speed.min,
            opacity: Math.random() * 0.5 + 0.3,
            shape: shapes[Math.floor(Math.random() * shapes.length)],
            rotation: Math.random() * 360,
            rotationSpeed: (Math.random() - 0.5) * 2,
            drift: (Math.random() - 0.5) * 0.5
        };
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
        this.ctx.rotate((particle.rotation * Math.PI) / 180);
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
        this.ctx.rotate((particle.rotation * Math.PI) / 180);
        this.ctx.beginPath();
        this.ctx.moveTo(0, -particle.size);
        this.ctx.lineTo(particle.size, 0);
        this.ctx.lineTo(0, particle.size);
        this.ctx.lineTo(-particle.size, 0);
        this.ctx.closePath();
        this.ctx.fill();
        this.ctx.restore();
    }

    updateParticle(particle) {
        particle.y += particle.speed;
        particle.x += particle.drift;
        particle.rotation += particle.rotationSpeed;

        // Reset particle when it goes off screen
        if (particle.y > this.canvas.height) {
            particle.y = -10;
            particle.x = Math.random() * this.canvas.width;
        }

        if (particle.x > this.canvas.width) {
            particle.x = 0;
        } else if (particle.x < 0) {
            particle.x = this.canvas.width;
        }
    }

    drawParticle(particle) {
        this.ctx.fillStyle = this.options.particleColor.replace('0.8', particle.opacity);

        if (this.options.glow) {
            this.ctx.shadowBlur = 10;
            this.ctx.shadowColor = this.options.particleColor;
        }

        switch (particle.shape) {
            case 'star':
                this.drawStar(particle);
                break;
            case 'diamond':
                this.drawDiamond(particle);
                break;
            default:
                this.drawCircle(particle);
        }

        if (this.options.glow) {
            this.ctx.shadowBlur = 0;
        }
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
        }
        this.particles = [];
        window.removeEventListener('resize', () => this.resizeCanvas());
        ParticleSystem.instances.delete(this.canvasId);
    }
}

// Make ParticleSystem available globally
window.ParticleSystem = ParticleSystem;

// Export for use in other files
export default ParticleSystem;
