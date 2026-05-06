import { S as Scene, P as PerspectiveCamera, W as WebGLRenderer, T as TextureLoader, f as PlaneGeometry, M as MeshBasicMaterial, g as Mesh, $ as $$, c as createWeakStore } from "./vendor.BOa7rJol.js";
const SELECTOR = "[data-three-image]";
const instances = createWeakStore();
const ThreeImage = {
  initAll(root = document) {
    $$(SELECTOR, root).forEach((el) => this.init(el));
  },
  init(el) {
    if (instances.has(el)) return;
    let options = {};
    try {
      options = JSON.parse(el.dataset.threeImage || "{}");
    } catch (e) {
    }
    const imgSrc = el.getAttribute("src");
    el.style.opacity = 0;
    const rect = el.getBoundingClientRect();
    const canvasWrap = document.createElement("div");
    canvasWrap.style.position = "absolute";
    canvasWrap.style.top = 0;
    canvasWrap.style.left = 0;
    canvasWrap.style.width = "100%";
    canvasWrap.style.height = "100%";
    el.parentElement.style.position = "relative";
    el.parentElement.appendChild(canvasWrap);
    const scene = new Scene();
    const camera = new PerspectiveCamera(
      75,
      rect.width / rect.height,
      0.1,
      1e3
    );
    const renderer = new WebGLRenderer({ alpha: true });
    renderer.setSize(rect.width, rect.height);
    canvasWrap.appendChild(renderer.domElement);
    camera.position.z = 2;
    const texture = new TextureLoader().load(imgSrc);
    const geometry = new PlaneGeometry(2, 2);
    const material = new MeshBasicMaterial({ map: texture });
    const mesh = new Mesh(geometry, material);
    scene.add(mesh);
    let frame;
    const animate = () => {
      if (options.effect === "float") {
        mesh.position.y = Math.sin(Date.now() * 1e-3) * 0.1;
      }
      if (options.effect === "rotate") {
        mesh.rotation.z += 0.01;
      }
      renderer.render(scene, camera);
      frame = requestAnimationFrame(animate);
    };
    animate();
    instances.set(el, { renderer, frame, canvasWrap });
  },
  destroy(el) {
    instances.cleanup(el, ({ renderer, frame, canvasWrap }) => {
      cancelAnimationFrame(frame);
      renderer.dispose();
      canvasWrap.remove();
      el.style.opacity = 1;
    });
  }
};
export {
  ThreeImage as default
};
//# sourceMappingURL=three-image.BAF47hR8.js.map
