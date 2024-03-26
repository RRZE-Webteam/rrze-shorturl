// Import necessary modules
import { registerBlockType } from '@wordpress/blocks';

// Import Edit and Save components
import metadata from './block.json';
import Edit from './edit.js';

// Register block type
registerBlockType( metadata.name, {
    title: metadata.title,
    edit: Edit, // Use the Edit component for editing
    save: () => null, // Empty save function
});