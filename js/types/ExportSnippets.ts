import { Pruuf } from './Pruuf'

export interface ExportPruufs {
	generator: string
	date_created: string
	Pruufs: Array<Pruuf>
}
