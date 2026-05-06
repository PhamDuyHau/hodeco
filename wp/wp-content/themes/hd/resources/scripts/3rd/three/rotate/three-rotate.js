import * as THREE from 'three';
import { $$ } from '../../dom.js';
import { createWeakStore } from '../../weak.js';

const SELECTOR = '[data-three-rotate]';
const instances = createWeakStore();

const ThreeRotate = {
	initAll(root = document) {
		$$(SELECTOR, root).forEach((el) => {
			this.init(el);
		});
	},

	init(el) {
		if (instances.has(el)) return;

		let options = {};
		try {
			options = JSON.parse(el.dataset.threeRotate || '{}');
		} catch (e) {
			console.warn('Invalid JSON in data-three-rotate', el);
		}

		// ❗ ONLY support IMG for now
		if (el.tagName !== 'IMG') {
			console.warn('three-rotate only supports <img>', el);
			return;
		}

		const width = el.clientWidth || 200;
		const height = el.clientHeight || 200;

		const scene = new THREE.Scene();

		const camera = new THREE.PerspectiveCamera(75, width / height, 0.1, 1000);
		camera.position.z = 2;

		const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
		renderer.setSize(width, height);

		// ✅ overlay instead of replacing layout
		renderer.domElement.style.position = 'absolute';
		renderer.domElement.style.top = 0;
		renderer.domElement.style.left = 0;
		renderer.domElement.style.width = '100%';
		renderer.domElement.style.height = '100%';

		el.parentElement.style.position = 'relative';
		el.parentElement.appendChild(renderer.domElement);

		// ✅ use image as texture
		const texture = new THREE.TextureLoader().load(el.src);

		const geometry = new THREE.PlaneGeometry(2, 2);
		const material = new THREE.MeshBasicMaterial({
			map: texture,
			transparent: true,
		});

		const mesh = new THREE.Mesh(geometry, material);
		scene.add(mesh);

		// ✅ hide original image
		el.style.opacity = 0;

		const speed = options.speed ?? 0.01;

		let frame;

		const animate = () => {
			mesh.rotation.z += speed;

			renderer.render(scene, camera);
			frame = requestAnimationFrame(animate);
		};

		frame = requestAnimationFrame(animate);

		instances.set(el, { renderer, frame });
	},


	destroy(el) {
		instances.cleanup(el, ({ renderer, frame }) => {
			cancelAnimationFrame(frame);
			renderer.dispose();
			el.innerHTML = '';
		});
	},

	destroyAll(root = document) {
		$$(SELECTOR, root).forEach((el) => this.destroy(el));
	},
};

export default ThreeRotate;
