import { Dispatch, SetStateAction } from 'react'
import { PruufActionsProps } from '../Edit/actions'
import { Pruuf } from './Pruuf'

export interface PruufInputProps {
	Pruuf: Pruuf
	setPruuf: Dispatch<SetStateAction<Pruuf>>
	isReadOnly: boolean
}

export interface PruufActionsInputProps extends PruufActionsProps, PruufInputProps {
	isWorking: boolean
}
