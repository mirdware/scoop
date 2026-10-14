import { Module } from 'scalar';
import themeToggleComponent from '@/scoop/components/theme-toogle';

export default new Module()
.compose('[data-theme-toggle]', themeToggleComponent);
