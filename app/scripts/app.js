import { Module } from 'scalar';
import Messenger from './services/Messenger';
import Message from './components/message';
import Form from './components/form';
import Printer from './components/printer';
import Pageable from './components/pageable';

new Module(Messenger)
.compose('#msg', Message)
.compose('.scoop-form', Form)
.compose('.printer', Printer)
.compose('.pageable', Pageable);
