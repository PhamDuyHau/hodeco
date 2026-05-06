import { $ as $$, S as Scene, P as PerspectiveCamera, W as WebGLRenderer, T as TextureLoader, f as PlaneGeometry, M as MeshBasicMaterial, g as Mesh, c as createWeakStore } from "./vendor.BOa7rJol.js";
const SELECTOR = "[data-three-float]";
const instances = createWeakStore();
const ThreeFloat = {
  initAll(root = document) {
    $$(SELECTOR, root).forEach((el) => {
      this.init(el);
    });
  },
  init(el) {
    if (instances.has(el)) return;
    let options = {};
    try {
      options = JSON.parse(el.dataset.threeFloat || "{}");
    } catch (e) {
      console.warn("Invalid JSON in data-three-float", el);
    }
    if (el.tagName !== "IMG") {
      console.warn("three-float only supports <img>", el);
      return;
    }
    const width = el.clientWidth || 200;
    const height = el.clientHeight || 200;
    const scene = new Scene();
    const camera = new PerspectiveCamera(75, width / height, 0.1, 1e3);
    camera.position.z = 2;
    const renderer = new WebGLRenderer({ alpha: true, antialias: true });
    renderer.setSize(width, height);
    renderer.domElement.style.position = "absolute";
    renderer.domElement.style.top = 0;
    renderer.domElement.style.left = 0;
    renderer.domElement.style.width = "100%";
    renderer.domElement.style.height = "100%";
    el.parentElement.style.position = "relative";
    el.parentElement.appendChild(renderer.domElement);
    const texture = new TextureLoader().load(el.src);
    const geometry = new PlaneGeometry(2, 2);
    const material = new MeshBasicMaterial({
      map: texture,
      transparent: true
    });
    const mesh = new Mesh(geometry, material);
    scene.add(mesh);
    el.style.opacity = 0;
    const speed = options.speed ?? 2;
    const range = options.range ?? 0.2;
    let frame;
    const animate = () => {
      mesh.position.y = Math.sin(Date.now() * 1e-3 * speed) * range;
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
      if (renderer.domElement && renderer.domElement.parentNode) {
        renderer.domElement.parentNode.removeChild(renderer.domElement);
      }
      el.style.opacity = 1;
    });
  },
  destroyAll(root = document) {
    $$(SELECTOR, root).forEach((el) => this.destroy(el));
  }
};
export {
  ThreeFloat as default
};
//# sourceMappingURL=three-float.LGqPXVFC.js.map
