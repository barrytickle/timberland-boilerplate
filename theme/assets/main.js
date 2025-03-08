import "./styles/main.scss";
import.meta.glob("../blocks/**/*.css", { eager: true });

import Alpine from "alpinejs";

window.Alpine = Alpine;

import.meta.glob("../blocks/**/*.js", { eager: true });
import.meta.glob("./scripts/**/*.js", { eager: true });

window.Alpine.start();
